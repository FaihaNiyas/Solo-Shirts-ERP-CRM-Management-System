<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Services\OrderProgressSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Derived Main-Order progress rollup. Aggregate status + counts are computed
 * purely from item states (single source of truth); nothing is stored on the
 * order. Covers the mixed-status cases the coarse OrderStatusDeriver cannot.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

/** Build an order whose items have exactly the given states. */
function orderWithStates($branch, array $states): Order
{
    $customer = Customer::factory()->for($branch)->create();
    $order = Order::factory()->for($branch)->for($customer)->create();
    foreach ($states as $state) {
        OrderItem::factory()->for($branch)->for($order)->create(['state' => $state]);
    }

    return $order->load('items');
}

function summarise(Order $order): array
{
    return app(OrderProgressSummary::class)->summarise($order->load('items')->items);
}

it('reports partially_ready for cutting + tailoring + ready_for_delivery', function () {
    $order = orderWithStates($this->branch, ['cutting', 'tailoring', 'ready_for_delivery']);

    $s = summarise($order);

    expect($s['aggregate_status'])->toBe('partially_ready')
        ->and($s['aggregate_status_label'])->toBe('Partially Ready')
        ->and($s['progress']['total'])->toBe(3)
        ->and($s['progress']['ready'])->toBe(1)
        ->and($s['progress']['in_production'])->toBe(2)
        ->and($s['progress']['delivered'])->toBe(0)
        ->and($s['summary_label'])->toBe('Partially Ready — 1 of 3 items ready');
});

it('reports partially_delivered for delivered + ready + tailoring', function () {
    $order = orderWithStates($this->branch, ['delivered', 'ready_for_delivery', 'tailoring']);

    $s = summarise($order);

    expect($s['aggregate_status'])->toBe('partially_delivered')
        ->and($s['progress']['delivered'])->toBe(1)
        ->and($s['progress']['ready'])->toBe(1)
        ->and($s['progress']['in_production'])->toBe(1)
        ->and($s['summary_label'])->toBe('Partially Delivered — 1 of 3 items delivered');
});

it('reports ready when all active items are ready_for_delivery', function () {
    $order = orderWithStates($this->branch, ['ready_for_delivery', 'ready_for_delivery']);

    $s = summarise($order);

    expect($s['aggregate_status'])->toBe('ready')
        ->and($s['summary_label'])->toBe('Ready for Pickup — 2 of 2 items ready');
});

it('reports delivered when all active items are delivered', function () {
    $order = orderWithStates($this->branch, ['delivered', 'delivered', 'delivered']);

    expect(summarise($order)['aggregate_status'])->toBe('delivered');
});

it('counts cancelled separately and excludes it from the X-of-N denominator', function () {
    // 1 ready + 1 tailoring + 1 cancelled → partially_ready, 1 of 2 (not 3).
    $order = orderWithStates($this->branch, ['ready_for_delivery', 'tailoring', 'cancelled']);

    $s = summarise($order);

    expect($s['aggregate_status'])->toBe('partially_ready')
        ->and($s['progress']['cancelled'])->toBe(1)
        ->and($s['progress']['active'])->toBe(2)
        ->and($s['summary_label'])->toBe('Partially Ready — 1 of 2 items ready');
});

it('reports cancelled only when every item is cancelled', function () {
    expect(summarise(orderWithStates($this->branch, ['cancelled', 'cancelled']))['aggregate_status'])->toBe('cancelled');
});

it('keeps single-item ready and delivered unchanged (no regression)', function () {
    expect(summarise(orderWithStates($this->branch, ['ready_for_delivery']))['aggregate_status'])->toBe('ready')
        ->and(summarise(orderWithStates($this->branch, ['delivered']))['aggregate_status'])->toBe('delivered');
});

it('never exposes a raw snake_case state as the human label', function () {
    expect(OrderProgressSummary::label('ready_for_delivery'))->toBe('Ready for Pickup')
        ->and(OrderProgressSummary::label('fabric_allocated'))->toBe('Fabric Ready')
        ->and(OrderProgressSummary::label('kaja_button'))->toBe('Kaja / Button');
});
