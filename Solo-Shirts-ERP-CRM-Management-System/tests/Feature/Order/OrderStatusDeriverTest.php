<?php

declare(strict_types=1);

use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Services\OrderStatusDeriver;
use Illuminate\Support\Collection;

/**
 * Pure-logic coverage for the derived order status (the backend never stores an
 * order status — it is computed from the items' production states). No DB: items
 * are built in memory and only their state attribute matters.
 */
function itemsInStates(string ...$states): Collection
{
    return collect($states)->map(fn (string $s): OrderItem => (new OrderItem)->forceFill(['state' => $s]));
}

beforeEach(function () {
    $this->deriver = app(OrderStatusDeriver::class);
});

it('reports cancelled when every item is cancelled', function () {
    expect($this->deriver->derive(itemsInStates(OrderItem::STATE_CANCELLED, OrderItem::STATE_CANCELLED)))
        ->toBe(OrderStatusDeriver::CANCELLED);
});

it('reports delivered when all active items are delivered (ignoring cancelled)', function () {
    expect($this->deriver->derive(itemsInStates(OrderItem::STATE_DELIVERED, OrderItem::STATE_CANCELLED)))
        ->toBe(OrderStatusDeriver::DELIVERED);
});

it('reports ready when active items are ready_for_delivery or delivered', function () {
    expect($this->deriver->derive(itemsInStates(OrderItem::STATE_READY_FOR_DELIVERY, OrderItem::STATE_DELIVERED)))
        ->toBe(OrderStatusDeriver::READY);
});

it('reports in_production when any item is mid-production', function () {
    expect($this->deriver->derive(itemsInStates(OrderItem::STATE_DRAFT, OrderItem::STATE_CUTTING)))
        ->toBe(OrderStatusDeriver::IN_PRODUCTION);
});

it('reports draft when items are only draft or fabric_allocated', function () {
    expect($this->deriver->derive(itemsInStates(OrderItem::STATE_DRAFT, OrderItem::STATE_FABRIC_ALLOCATED)))
        ->toBe(OrderStatusDeriver::DRAFT);
});
