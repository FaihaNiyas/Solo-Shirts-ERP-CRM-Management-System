<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Order\Models\OrderItem;
use Illuminate\Support\Collection;

/**
 * Derives an order's status purely from its items' states. Order status is
 * never stored — always computed from order_items.
 */
final class OrderStatusDeriver
{
    public const DRAFT = 'draft';

    public const IN_PRODUCTION = 'in_production';

    public const READY = 'ready';

    public const DELIVERED = 'delivered';

    public const CANCELLED = 'cancelled';

    /**
     * @param  Collection<int, OrderItem>  $items
     */
    public function derive(Collection $items): string
    {
        $active = $items->reject(fn (OrderItem $item): bool => (string) $item->state === OrderItem::STATE_CANCELLED);

        if ($active->isEmpty()) {
            return self::CANCELLED;
        }

        $states = $active->map(fn (OrderItem $item): string => (string) $item->state);

        if ($states->every(fn (string $s): bool => $s === OrderItem::STATE_DELIVERED)) {
            return self::DELIVERED;
        }

        if ($states->every(fn (string $s): bool => in_array($s, [OrderItem::STATE_READY_FOR_DELIVERY, OrderItem::STATE_DELIVERED], true))) {
            return self::READY;
        }

        if ($states->contains(fn (string $s): bool => in_array($s, OrderItem::IN_PRODUCTION_STATES, true))) {
            return self::IN_PRODUCTION;
        }

        if ($states->every(fn (string $s): bool => in_array($s, [OrderItem::STATE_DRAFT, OrderItem::STATE_FABRIC_ALLOCATED], true))) {
            return self::DRAFT;
        }

        return self::IN_PRODUCTION;
    }
}
