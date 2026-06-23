<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Inventory\Http\Resources\DamageReportListResource;
use App\Modules\Inventory\Http\Resources\DamageReportResource;
use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Inventory\Services\DamageReportService;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Http\Requests\ReportItemDamageRequest;
use App\Modules\Production\Models\FabricAllocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Phase 7B — cloth damage / waste reported from the production workbench for a
 * single sub-order. This is deliberately separate from alteration and QC rework:
 * it records fabric lost during production. The fabric roll, order and order item
 * are derived from the item's allocation, so the floor only picks a stage, type
 * and metres. Reports start `pending` and only deduct stock on owner approval
 * (the existing Phase 12 path) — this endpoint never touches stock.
 */
final class ProductionClothDamageController extends BaseApiController
{
    public function __construct(private readonly DamageReportService $reports) {}

    /** Damage reports raised against this item (read — any production viewer). */
    public function index(OrderItem $item): JsonResponse
    {
        $this->authorize('view', OrderItem::class);

        $reports = DamageReport::query()
            ->where('order_item_id', $item->id)
            ->latest('id')
            ->get();

        return $this->respond(DamageReportListResource::collection($reports)->resolve());
    }

    /** Report cloth damage for this item; the roll comes from its allocation. */
    public function store(ReportItemDamageRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('create', DamageReport::class);

        /** @var FabricAllocation|null $allocation */
        $allocation = FabricAllocation::query()
            ->where('order_item_id', $item->id)
            ->whereIn('status', [FabricAllocation::STATUS_RESERVED, FabricAllocation::STATUS_CONSUMED])
            ->latest('id')
            ->first();

        if ($allocation === null) {
            throw ValidationException::withMessages([
                'fabric' => 'Allocate fabric to this item before reporting cloth damage.',
            ]);
        }

        /** @var User $actor */
        $actor = $request->user();

        $report = $this->reports->report([
            ...$request->validated(),
            'fabric_roll_id' => $allocation->fabric_roll_id,
            'order_id' => $item->order_id,
            'order_item_id' => $item->id,
        ], $actor);

        return $this->respond((new DamageReportResource($report))->resolve(), 'Cloth damage reported', 201);
    }
}
