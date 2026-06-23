<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Inventory\Contracts\StockLedgerInterface;
use App\Modules\Inventory\Http\Requests\AdjustRollRequest;
use App\Modules\Inventory\Http\Requests\CreateFabricRollRequest;
use App\Modules\Inventory\Http\Requests\FabricRollThresholdRequest;
use App\Modules\Inventory\Http\Resources\FabricRollResource;
use App\Modules\Inventory\Http\Resources\MovementResource;
use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Inventory\Services\FabricRollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FabricRollController extends BaseApiController
{
    public function __construct(
        private readonly FabricRollService $rolls,
        private readonly StockLedgerInterface $ledger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('view', FabricRoll::class);

        $query = FabricRoll::query()->latest('id');

        if ($request->filled('type')) {
            $query->where('fabric_type_id', $request->integer('type'));
        }
        if ($request->filled('colour')) {
            $query->where('colour', (string) $request->string('colour'));
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        return $this->respond(FabricRollResource::collection($query->paginate(20)->items())->resolve());
    }

    public function store(CreateFabricRollRequest $request): JsonResponse
    {
        $this->authorize('createRoll', FabricRoll::class);

        /** @var User $actor */
        $actor = $request->user();
        $roll = $this->rolls->create($request->validated(), $actor);

        return $this->respond((new FabricRollResource($roll))->resolve(), 'Fabric roll created', 201);
    }

    public function show(FabricRoll $fabricRoll): JsonResponse
    {
        $this->authorize('view', FabricRoll::class);

        return $this->respond((new FabricRollResource($fabricRoll))->resolve());
    }

    public function adjust(AdjustRollRequest $request, FabricRoll $fabricRoll): JsonResponse
    {
        $this->authorize('adjustRoll', FabricRoll::class);

        /** @var User $actor */
        $actor = $request->user();
        $reason = $request->filled('reason') ? (string) $request->string('reason') : null;

        $roll = $this->rolls->adjust(
            $fabricRoll->id,
            (string) $request->string('type'),
            (float) $request->float('metres'),
            $reason,
            $actor,
        );

        return $this->respond((new FabricRollResource($roll))->resolve(), 'Roll adjusted');
    }

    /**
     * Phase 8A — a roll's stock ledger: its movement history plus the live
     * available/reserved/consumed/damaged breakdown. The roll binds through the
     * branch global scope (cross-branch → 404), so the movements are branch-safe.
     */
    public function ledger(FabricRoll $fabricRoll): JsonResponse
    {
        $this->authorize('view', FabricRoll::class);

        $movements = FabricMovement::query()
            ->where('fabric_roll_id', $fabricRoll->id)
            ->latest('id')
            ->limit(100)
            ->get();

        $b = $this->ledger->breakdown($fabricRoll);
        $fmt = static fn (float $v): string => number_format($v, 2, '.', '');

        return $this->respond([
            'roll' => (new FabricRollResource($fabricRoll))->resolve(),
            'breakdown' => [
                'remaining_metres' => $fmt($b['remaining']),
                'available_metres' => $fmt($b['available']),
                'reserved_metres' => $fmt($b['reserved']),
                'consumed_metres' => $fmt($b['consumed']),
                'damaged_metres' => $fmt($b['damaged']),
            ],
            'movements' => MovementResource::collection($movements)->resolve(),
        ]);
    }

    /** Phase 8A — set or clear the roll's per-roll low-stock reorder threshold. */
    public function threshold(FabricRollThresholdRequest $request, FabricRoll $fabricRoll): JsonResponse
    {
        $this->authorize('adjustRoll', FabricRoll::class);

        $fabricRoll->update([
            'low_stock_threshold_metres' => $request->input('low_stock_threshold_metres'),
        ]);

        return $this->respond((new FabricRollResource($fabricRoll->fresh()))->resolve(), 'Threshold updated');
    }
}
