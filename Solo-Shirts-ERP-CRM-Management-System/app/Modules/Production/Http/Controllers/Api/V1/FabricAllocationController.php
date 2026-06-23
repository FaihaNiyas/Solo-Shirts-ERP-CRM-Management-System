<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Http\Requests\AllocateFabricRequest;
use App\Modules\Production\Http\Requests\ReleaseFabricRequest;
use App\Modules\Production\Http\Resources\FabricAllocationResource;
use App\Modules\Production\Services\FabricAllocationService;
use Illuminate\Http\JsonResponse;

final class FabricAllocationController extends BaseApiController
{
    public function __construct(private readonly FabricAllocationService $allocations) {}

    public function store(AllocateFabricRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('allocateFabric', $item);

        /** @var User $actor */
        $actor = $request->user();
        $key = (string) $request->header('Idempotency-Key');

        $allocation = $this->allocations->reserve(
            $item->id,
            (int) $request->integer('roll_id'),
            (float) $request->float('metres'),
            $actor,
            $key,
        );

        return $this->respond((new FabricAllocationResource($allocation))->resolve(), 'Fabric reserved', 201);
    }

    public function release(ReleaseFabricRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('releaseFabric', $item);

        /** @var User $actor */
        $actor = $request->user();
        $reason = $request->filled('reason') ? (string) $request->string('reason') : null;

        $allocation = $this->allocations->release($item->id, $reason, $actor);

        return $this->respond((new FabricAllocationResource($allocation->load('roll')))->resolve(), 'Reservation released');
    }
}
