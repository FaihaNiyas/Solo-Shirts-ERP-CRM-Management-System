<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Delivery\Exceptions\RackException;
use App\Modules\Delivery\Http\Requests\CreateSlotRequest;
use App\Modules\Delivery\Http\Requests\UpdateSlotRequest;
use App\Modules\Delivery\Http\Resources\RackSlotResource;
use App\Modules\Delivery\Models\RackSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RackSlotController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RackSlot::class);

        $query = RackSlot::query()->orderBy('slot_code');

        if ($request->filled('occupied')) {
            $request->boolean('occupied')
                ? $query->whereNotNull('current_order_item_id')
                : $query->whereNull('current_order_item_id');
        }

        return $this->respond(RackSlotResource::collection($query->get())->resolve());
    }

    public function store(CreateSlotRequest $request): JsonResponse
    {
        $this->authorize('manage', RackSlot::class);

        /** @var User $actor */
        $actor = $request->user();

        $slot = RackSlot::query()->create([
            'branch_id' => $actor->branch_id,
            'slot_code' => (string) $request->string('slot_code'),
            'label' => $request->filled('label') ? (string) $request->string('label') : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->respond((new RackSlotResource($slot))->resolve(), 'Rack slot created', 201);
    }

    public function update(UpdateSlotRequest $request, RackSlot $rackSlot): JsonResponse
    {
        $this->authorize('manage', RackSlot::class);

        // A slot may not be deactivated while it holds an item.
        if ($request->has('is_active') && $request->boolean('is_active') === false && $rackSlot->isOccupied()) {
            throw RackException::slotOccupied();
        }

        $rackSlot->fill([
            'label' => $request->has('label') ? $request->input('label') : $rackSlot->label,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $rackSlot->is_active,
        ])->save();

        return $this->respond((new RackSlotResource($rackSlot))->resolve(), 'Rack slot updated');
    }
}
