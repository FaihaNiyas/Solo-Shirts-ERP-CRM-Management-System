<?php

declare(strict_types=1);

namespace App\Modules\Production\Listeners;

use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Events\OrderItemStateChanged;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued listener that notifies the customer when their item is ready. The
 * actual WhatsApp/email dispatch is delivered by Phase 17; this fires only on
 * the ReadyForDelivery edge and is a no-op until that channel exists.
 */
final class NotifyOnReadyForDelivery implements ShouldQueue
{
    public function handle(OrderItemStateChanged $event): void
    {
        if ($event->to !== OrderItem::STATE_READY_FOR_DELIVERY) {
            return;
        }

        // Phase 17 wires the notification channel here.
    }
}
