<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Exceptions\ProductionException;
use Illuminate\Support\Facades\DB;

/**
 * Puts a production item on hold / resumes it. "On hold" is an overlay flag, NOT a
 * state-machine state: the item keeps its real production state, so the linear flow
 * and downstream listeners (rack, delivery) are never disrupted. A delivered or
 * cancelled item is terminal and cannot be held.
 */
final class ProductionHoldService
{
    public function hold(OrderItem $item, string $reason, User $actor): OrderItem
    {
        return DB::transaction(function () use ($item, $reason): OrderItem {
            /** @var OrderItem $locked */
            $locked = OrderItem::query()->lockForUpdate()->findOrFail($item->id);

            if (in_array((string) $locked->state, [OrderItem::STATE_DELIVERED, OrderItem::STATE_CANCELLED], true)) {
                throw ProductionException::cannotHoldTerminal();
            }

            if ($locked->isOnHold()) {
                throw ProductionException::alreadyOnHold();
            }

            $locked->update(['on_hold_at' => now(), 'on_hold_reason' => $reason]);

            return $locked;
        });
    }

    public function resume(OrderItem $item, User $actor): OrderItem
    {
        return DB::transaction(function () use ($item): OrderItem {
            /** @var OrderItem $locked */
            $locked = OrderItem::query()->lockForUpdate()->findOrFail($item->id);

            if (!$locked->isOnHold()) {
                throw ProductionException::notOnHold();
            }

            $locked->update(['on_hold_at' => null, 'on_hold_reason' => null]);

            return $locked;
        });
    }
}
