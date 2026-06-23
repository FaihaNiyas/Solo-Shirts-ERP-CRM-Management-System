<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Exceptions\PackingException;
use App\Modules\Production\Models\PackingChecklist;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Final packing for a sub-order. An item can only be packed once it has passed QC
 * and entered the packing stage (the state machine only reaches Packing from Qc),
 * so the packing-state guard inherently enforces "QC passed first". Marking packed
 * promotes the item to ready-for-delivery, which fires the existing rack
 * auto-assign listener. It never marks delivered and never touches the balance —
 * the balance gate stays at handover.
 */
final class PackingService
{
    public function __construct(private readonly StateTransitionService $transitions) {}

    public function find(int $itemId): ?PackingChecklist
    {
        return PackingChecklist::query()->where('order_item_id', $itemId)->first();
    }

    /**
     * Upsert the packing checklist for an item (one row per item). Only allowed
     * while the item is in the packing stage.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveChecklist(int $itemId, array $data, User $actor): PackingChecklist
    {
        $item = OrderItem::query()->findOrFail($itemId);

        $this->assertInPacking($item);

        $checklist = PackingChecklist::query()->firstOrNew(['order_item_id' => $item->id]);

        $checklist->fill([
            'branch_id' => $item->branch_id,
            'order_id' => $item->order_id,
            ...array_intersect_key($data, array_flip([...PackingChecklist::REQUIRED_CHECKS, 'notes'])),
        ]);
        $checklist->save();

        return $checklist;
    }

    /**
     * Stamp the checklist as packed and promote the item to ready-for-delivery
     * (which auto-assigns a rack slot via the existing listener).
     */
    public function markPacked(int $itemId, User $actor): OrderItem
    {
        return DB::transaction(function () use ($itemId, $actor): OrderItem {
            $item = OrderItem::query()->lockForUpdate()->findOrFail($itemId);

            $this->assertInPacking($item);

            $checklist = PackingChecklist::query()->where('order_item_id', $item->id)->first();

            if ($checklist === null || !$checklist->isComplete()) {
                throw PackingException::checklistIncomplete();
            }

            $checklist->update(['packed_by' => $actor->id, 'packed_at' => now()]);

            // Promote to ready-for-delivery. The transition fires
            // OnReadyForDeliveryAssignSlot, which assigns a rack slot.
            $this->transitions->transition(
                $item->id,
                OrderItem::STATE_READY_FOR_DELIVERY,
                $actor,
                (string) Str::uuid(),
                'packed and moved to ready rack',
                ['packed' => true],
            );

            return $item->fresh();
        });
    }

    private function assertInPacking(OrderItem $item): void
    {
        if ((string) $item->state !== OrderItem::STATE_PACKING) {
            throw PackingException::notInPacking();
        }
    }
}
