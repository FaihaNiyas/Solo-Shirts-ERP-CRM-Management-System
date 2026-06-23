<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Delivery pickup box: the box / shelf number is entered on the board at the final
 * move (Ready for Delivery / Delivered) and stored on the item, so the Front Desk
 * can search it on collection.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->supervisor = makeUser($this->branch, 'Production Supervisor');
    $this->fd = makeUser($this->branch, 'Front Desk');
});

it('records the delivery box number when staging an item for delivery', function () {
    $item = productionItem($this->branch, 'packing');

    transitionItem($this, $this->supervisor, $item->id, [
        'to' => 'ready_for_delivery',
        'delivery_box_code' => 'B-12',
    ])->assertOk()->assertJsonPath('data.delivery_box_code', 'B-12');

    expect($item->fresh()->delivery_box_code)->toBe('B-12');
});

it('keeps the delivery box across the final delivered move when none is given', function () {
    $item = productionItem($this->branch, 'ready_for_delivery');
    $item->update(['delivery_box_code' => 'B-7']);

    transitionItem($this, $this->supervisor, $item->id, ['to' => 'delivered'])->assertOk();

    // An empty box on a later move must not wipe the recorded location.
    expect($item->fresh()->delivery_box_code)->toBe('B-7');
});

it('lets the Front Desk find an order by its delivery box number', function () {
    // A confirmed order with a known customer, staged into a delivery box.
    $customer = Customer::factory()->for($this->branch)->create(['name' => 'Ravi Kumar']);
    $order = Order::factory()->for($this->branch)->for($customer)->create();
    $item = OrderItem::factory()->for($this->branch)->for($order)->create(['state' => 'packing']);

    transitionItem($this, $this->supervisor, $item->id, [
        'to' => 'ready_for_delivery',
        'delivery_box_code' => 'B-99',
    ])->assertOk();

    $res = $this->withHeaders(bearer($this->fd))
        ->getJson('/api/v1/orders/lookup?q=B-99')
        ->assertOk();

    expect($res->json('data.results.0.order_code'))->toBe($order->order_code)
        ->and($res->json('data.results.0.items.0.delivery_box_code'))->toBe('B-99');
});
