<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Services;

use App\Models\User;
use App\Modules\Delivery\Exceptions\RackException;
use App\Modules\Delivery\Models\RackAssignment;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Assigns and releases rack slots for items that are ready for delivery. Single
 * occupancy is guaranteed at the DB (slot row lock + the partial-unique
 * assignment ledger); the app-level checks only provide friendly error codes.
 */
final class RackSlotService
{
    public function assign(int $itemId, ?string $slotCode, ?User $actor): RackAssignment
    {
        return DB::transaction(function () use ($itemId, $slotCode, $actor): RackAssignment {
            $item = OrderItem::query()->findOrFail($itemId);

            $alreadyAssigned = RackAssignment::query()
                ->where('order_item_id', $item->id)
                ->whereNull('released_at')
                ->exists();

            if ($alreadyAssigned) {
                throw RackException::itemAlreadyAssigned();
            }

            $slot = $this->pickSlot($item->branch_id, $slotCode);

            try {
                $assignment = RackAssignment::query()->create([
                    'rack_slot_id' => $slot->id,
                    'order_item_id' => $item->id,
                    'branch_id' => $item->branch_id,
                    'assigned_at' => now(),
                    'assigned_by' => $actor?->id,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Lost the race for this slot / item at the DB level.
                throw RackException::slotOccupied();
            }

            $slot->update([
                'current_order_item_id' => $item->id,
                'occupied_at' => now(),
            ]);

            return $assignment;
        });
    }

    public function release(int $itemId, ?string $reason, ?User $actor): RackAssignment
    {
        return DB::transaction(function () use ($itemId, $reason, $actor): RackAssignment {
            /** @var RackAssignment|null $assignment */
            $assignment = RackAssignment::query()
                ->where('order_item_id', $itemId)
                ->whereNull('released_at')
                ->lockForUpdate()
                ->first();

            if ($assignment === null) {
                throw RackException::notAssigned();
            }

            $slot = RackSlot::query()->lockForUpdate()->find($assignment->rack_slot_id);

            $assignment->update([
                'released_at' => now(),
                'released_by' => $actor?->id,
                'release_reason' => $reason,
            ]);

            $slot?->update([
                'current_order_item_id' => null,
                'occupied_at' => null,
            ]);

            return $assignment;
        });
    }

    private function pickSlot(int $branchId, ?string $slotCode): RackSlot
    {
        if ($slotCode !== null) {
            /** @var RackSlot|null $slot */
            $slot = RackSlot::query()
                ->where('branch_id', $branchId)
                ->where('slot_code', $slotCode)
                ->lockForUpdate()
                ->first();

            if ($slot === null) {
                throw RackException::slotNotFound();
            }

            if (!$slot->is_active) {
                throw RackException::slotInactive();
            }

            if ($slot->current_order_item_id !== null) {
                throw RackException::slotOccupied();
            }

            return $slot;
        }

        /** @var RackSlot|null $slot */
        $slot = RackSlot::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereNull('current_order_item_id')
            ->orderBy('slot_code')
            ->lockForUpdate()
            ->first();

        if ($slot === null) {
            throw RackException::noSlotAvailable();
        }

        return $slot;
    }
}
