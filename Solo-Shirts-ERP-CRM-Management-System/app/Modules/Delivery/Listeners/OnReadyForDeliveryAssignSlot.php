<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Listeners;

use App\Models\User;
use App\Modules\Delivery\Exceptions\RackException;
use App\Modules\Delivery\Services\RackSlotService;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Events\OrderItemStateChanged;

/**
 * Auto-assigns the first available rack slot when an item reaches
 * ReadyForDelivery. Runs inside the Phase 7 transition transaction, so a full
 * rack must NOT abort the transition — RackException is caught and logged.
 */
final class OnReadyForDeliveryAssignSlot
{
    public function __construct(private readonly RackSlotService $rackSlots) {}

    public function handle(OrderItemStateChanged $event): void
    {
        if ($event->to !== OrderItem::STATE_READY_FOR_DELIVERY) {
            return;
        }

        $actor = $event->actorId !== null ? User::query()->find($event->actorId) : null;

        try {
            $this->rackSlots->assign($event->orderItemId, null, $actor);
        } catch (RackException $e) {
            activity('delivery')
                ->event('rack-auto-assign-skipped')
                ->withProperties(['order_item_id' => $event->orderItemId, 'code' => $e->errorCode()])
                ->log('rack auto-assign skipped: ' . $e->getMessage());
        }
    }
}
