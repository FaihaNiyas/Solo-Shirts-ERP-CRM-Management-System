<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Http\Requests\AllocateFabricRequest;
use App\Modules\Production\Http\Requests\ConsumeFabricRequest;
use App\Modules\Production\Http\Resources\FabricAllocationResource;
use App\Modules\Production\Models\FabricAllocation;
use App\Modules\Production\Services\FabricAllocationService;
use Illuminate\Http\JsonResponse;

/**
 * Phase 7B — fabric allocation surfaced on the production workbench, scoped to a
 * single sub-order (order item). A thin convenience layer over the Phase 8
 * FabricAllocationService so the floor can reserve, view and mark-consumed fabric
 * without leaving the production screen. Branch isolation comes from the OrderItem
 * global scope (cross-branch binding 404s). Front Desk has none of these
 * permissions, so the panel never renders for them.
 */
final class ProductionFabricController extends BaseApiController
{
    public function __construct(private readonly FabricAllocationService $allocations) {}

    /** Current allocation + history for the item (read — any production viewer). */
    public function show(OrderItem $item): JsonResponse
    {
        $this->authorize('view', OrderItem::class);

        $history = FabricAllocation::query()
            ->where('order_item_id', $item->id)
            ->with('roll')
            ->latest('id')
            ->get();

        $active = $history->firstWhere('status', FabricAllocation::STATUS_RESERVED)
            ?? $history->firstWhere('status', FabricAllocation::STATUS_CONSUMED);

        return $this->respond([
            'active' => $active === null ? null : (new FabricAllocationResource($active))->resolve(),
            'history' => FabricAllocationResource::collection($history)->resolve(),
        ]);
    }

    /** Reserve fabric against the item (idempotent). */
    public function store(AllocateFabricRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('allocateFabric', $item);

        /** @var User $actor */
        $actor = $request->user();

        $allocation = $this->allocations->reserve(
            $item->id,
            (int) $request->integer('roll_id'),
            (float) $request->float('metres'),
            $actor,
            (string) $request->header('Idempotency-Key'),
        );

        return $this->respond((new FabricAllocationResource($allocation))->resolve(), 'Fabric reserved', 201);
    }

    /** Record actual usage and close the reservation as consumed. */
    public function consume(ConsumeFabricRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('allocateFabric', $item);

        /** @var User $actor */
        $actor = $request->user();
        $actual = $request->filled('actual_metres') ? (float) $request->float('actual_metres') : null;

        $allocation = $this->allocations->consume($item->id, $actual, $actor);

        return $this->respond((new FabricAllocationResource($allocation))->resolve(), 'Fabric marked consumed');
    }
}
