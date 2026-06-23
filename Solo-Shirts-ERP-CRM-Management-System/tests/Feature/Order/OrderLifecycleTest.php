<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Front Desk');
    $this->customer = Customer::factory()->for($this->branch)->create();
    $this->version = approvedVersionFor($this->branch, $this->customer);
});

function createOrderWithItems(int $count = 1): int
{
    $items = [];
    for ($i = 0; $i < $count; $i++) {
        $items[] = ['product_type' => 'shirt', 'quantity' => 1, 'measurement_version_id' => test()->version->id];
    }

    return test()->withHeaders(bearer(test()->user) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload(test()->customer->id, test()->version->id, ['items' => $items]))
        ->assertCreated()
        ->json('data.id');
}

it('derives order status from item states', function () {
    $orderId = createOrderWithItems(2);

    // All items ready_for_delivery -> order "ready".
    OrderItem::query()->where('order_id', $orderId)->update(['state' => OrderItem::STATE_READY_FOR_DELIVERY]);
    $this->withHeaders(bearer($this->user))->getJson("/api/v1/orders/{$orderId}")
        ->assertOk()->assertJson(['data' => ['status' => 'ready']]);

    // One item in cutting -> order "in_production".
    $firstItem = OrderItem::query()->where('order_id', $orderId)->orderBy('id')->first();
    $firstItem->update(['state' => OrderItem::STATE_CUTTING]);
    $this->withHeaders(bearer($this->user))->getJson("/api/v1/orders/{$orderId}")
        ->assertOk()->assertJson(['data' => ['status' => 'in_production']]);
});

it('forbids editing an item once fabric is allocated (409 INVALID_STATE_FOR_EDIT)', function () {
    $orderId = createOrderWithItems(1);
    $item = OrderItem::query()->where('order_id', $orderId)->firstOrFail();
    $item->update(['state' => OrderItem::STATE_FABRIC_ALLOCATED]);

    $this->withHeaders(bearer($this->user))
        ->putJson("/api/v1/orders/{$orderId}/items/{$item->id}", ['fabric_preference_text' => 'Linen'])
        ->assertStatus(409)
        ->assertJson(['code' => 'INVALID_STATE_FOR_EDIT']);
});

it('cancels an order while items are before cutting', function () {
    $orderId = createOrderWithItems(2);

    $this->withHeaders(bearer($this->user))
        ->postJson("/api/v1/orders/{$orderId}/cancel", ['reason' => 'Customer changed mind'])
        ->assertOk();

    $states = OrderItem::query()->where('order_id', $orderId)->get()
        ->map(fn (OrderItem $item): string => (string) $item->state)
        ->unique()->values()->all();
    expect($states)->toBe(['cancelled']);
});

it('forbids cancelling an order once an item has passed into production (409)', function () {
    $orderId = createOrderWithItems(1);
    OrderItem::query()->where('order_id', $orderId)->update(['state' => OrderItem::STATE_PACKING]);

    $this->withHeaders(bearer($this->user))
        ->postJson("/api/v1/orders/{$orderId}/cancel", ['reason' => 'too late'])
        ->assertStatus(409)
        ->assertJson(['code' => 'INVALID_STATE_FOR_CANCEL']);
});

it('derives order status ready only after the order exists', function () {
    $orderId = createOrderWithItems(1);

    expect(Order::query()->whereKey($orderId)->exists())->toBeTrue();
});
