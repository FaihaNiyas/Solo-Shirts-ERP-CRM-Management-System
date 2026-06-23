<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Delivery\Http\Requests\AssignSlotRequest;
use App\Modules\Delivery\Http\Requests\ReleaseSlotRequest;
use App\Modules\Delivery\Http\Resources\RackAssignmentResource;
use App\Modules\Delivery\Http\Resources\RackSlotResource;
use App\Modules\Delivery\Models\RackAssignment;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Delivery\Services\RackSlotService;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Http\JsonResponse;

final class RackAssignmentController extends BaseApiController
{
    public function __construct(private readonly RackSlotService $rackSlots) {}

    public function assign(AssignSlotRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('assign', RackSlot::class);

        /** @var User $actor */
        $actor = $request->user();
        $slotCode = $request->filled('slot_code') ? (string) $request->string('slot_code') : null;

        $assignment = $this->rackSlots->assign($item->id, $slotCode, $actor);

        return $this->respond((new RackAssignmentResource($assignment))->resolve(), 'Item assigned to slot', 201);
    }

    public function release(ReleaseSlotRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('release', RackSlot::class);

        /** @var User $actor */
        $actor = $request->user();
        $reason = $request->filled('reason') ? (string) $request->string('reason') : null;

        $assignment = $this->rackSlots->release($item->id, $reason, $actor);

        return $this->respond((new RackAssignmentResource($assignment))->resolve(), 'Slot released');
    }

    public function currentSlot(OrderItem $item): JsonResponse
    {
        $this->authorize('viewAny', RackSlot::class);

        /** @var RackAssignment|null $assignment */
        $assignment = RackAssignment::query()
            ->where('order_item_id', $item->id)
            ->whereNull('released_at')
            ->first();

        if ($assignment === null) {
            return $this->respond(null, 'No active slot');
        }

        $slot = RackSlot::query()->find($assignment->rack_slot_id);

        return $this->respond($slot === null ? null : (new RackSlotResource($slot))->resolve());
    }
}
