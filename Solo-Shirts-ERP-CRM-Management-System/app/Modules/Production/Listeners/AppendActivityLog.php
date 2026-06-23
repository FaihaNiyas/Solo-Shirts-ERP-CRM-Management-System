<?php

declare(strict_types=1);

namespace App\Modules\Production\Listeners;

use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Events\OrderItemStateChanged;

/**
 * Audit listener: records every production transition in the activity log. Runs
 * synchronously so the audit trail is durable within the transition request.
 */
final class AppendActivityLog
{
    public function handle(OrderItemStateChanged $event): void
    {
        $item = OrderItem::withoutGlobalScopes()->find($event->orderItemId);

        $logger = activity('production')
            ->event('state-changed')
            ->withProperties([
                'from' => $event->from,
                'to' => $event->to,
                'actor_id' => $event->actorId,
                'metadata' => $event->metadata,
            ]);

        if ($item !== null) {
            $logger->performedOn($item);
        }

        $logger->log("order_item {$event->orderItemId}: {$event->from} -> {$event->to}");
    }
}
