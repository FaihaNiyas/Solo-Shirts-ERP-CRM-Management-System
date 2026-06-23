<?php

declare(strict_types=1);

namespace App\Modules\Production\Listeners;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Events\OrderItemStateChanged;

/**
 * Order status is always derived from item states, never stored. This listener
 * touches the parent order so any downstream read-through cache / ETag keyed on
 * the order's updated_at is invalidated when an item moves.
 */
final class RecomputeOrderDerivedStatus
{
    public function handle(OrderItemStateChanged $event): void
    {
        $item = OrderItem::withoutGlobalScopes()->find($event->orderItemId);

        if ($item === null) {
            return;
        }

        Order::withoutGlobalScopes()->whereKey($item->order_id)->first()?->touch();
    }
}
