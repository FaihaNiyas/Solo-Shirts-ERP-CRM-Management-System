<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Listeners;

use App\Models\User;
use App\Modules\Delivery\Exceptions\RackException;
use App\Modules\Delivery\Services\RackSlotService;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Events\OrderItemStateChanged;

/**
 * Frees an item's rack slot when it is delivered or cancelled. If the item was
 * never racked, the "not assigned" error is simply swallowed.
 */
final class OnDeliveredOrCancelledReleaseSlot
{
    public function __construct(private readonly RackSlotService $rackSlots) {}

    public function handle(OrderItemStateChanged $event): void
    {
        if (!in_array($event->to, [OrderItem::STATE_DELIVERED, OrderItem::STATE_CANCELLED], true)) {
            return;
        }

        $actor = $event->actorId !== null ? User::query()->find($event->actorId) : null;

        try {
            $this->rackSlots->release($event->orderItemId, 'auto-released on ' . $event->to, $actor);
        } catch (RackException) {
            // The item never occupied a slot — nothing to release.
        }
    }
}
