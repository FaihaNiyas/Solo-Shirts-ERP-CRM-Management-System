<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->fd = makeUser($this->branch, 'Front Desk');
});

/**
 * Confirmed order (box + invoice + advance) for the given context. Returns
 * order/item ids and codes plus the customer.
 *
 * @return array{order_id:int,item_id:int,order_code:string,item_code:string,customer:Customer}
 */
function lookupReadyOrder($ctx, array $customerAttrs = []): array
{
    $customer = Customer::factory()->for($ctx->branch)->create($customerAttrs);
    $version = approvedVersionFor($ctx->branch, $customer);

    $res = test()->withHeaders(bearer($ctx->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($customer->id, $version->id, [
            'items' => [['product_type' => 'shirt', 'quantity' => 1, 'measurement_version_id' => $version->id]],
            'lifecycle_status' => 'intake_preparation',
        ]))->assertCreated();

    $orderId = $res->json('data.id');
    $itemId = $res->json('data.items.0.id');

    test()->withHeaders(bearer($ctx->fd))->getJson("/api/v1/orders/{$orderId}/items/{$itemId}/job-card")->assertCreated();
    test()->withHeaders(bearer($ctx->fd))->postJson("/api/v1/orders/{$orderId}/confirm", [
        'pricing' => ['total_amount' => 2000],
        'payment' => ['advance_amount' => 500, 'method' => 'cash'],
    ])->assertOk();

    return [
        'order_id' => $orderId,
        'item_id' => $itemId,
        'order_code' => Order::query()->find($orderId)->order_code,
        'item_code' => OrderItem::query()->find($itemId)->item_code,
        'customer' => $customer,
    ];
}

it('looks up orders by phone last-4', function () {
    $o = lookupReadyOrder($this, ['phone_last4' => '4321']);

    $this->withHeaders(bearer($this->fd))->getJson('/api/v1/orders/lookup?q=4321')
        ->assertOk()
        ->assertJsonPath('data.results.0.order_code', $o['order_code']);
});

it('looks up an order by its main code', function () {
    $o = lookupReadyOrder($this);

    $this->withHeaders(bearer($this->fd))->getJson('/api/v1/orders/lookup?q=' . $o['order_code'])
        ->assertOk()
        ->assertJsonPath('data.results.0.order_code', $o['order_code']);
});

it('looks up the parent order by a sub-order code', function () {
    $o = lookupReadyOrder($this);

    $res = $this->withHeaders(bearer($this->fd))->getJson('/api/v1/orders/lookup?q=' . $o['item_code'])->assertOk();

    expect($res->json('data.results.0.order_code'))->toBe($o['order_code'])
        ->and(collect($res->json('data.results.0.items'))->pluck('item_code'))->toContain($o['item_code']);
});

it('includes the invoice balance', function () {
    $o = lookupReadyOrder($this); // total 2000, advance 500 → balance 1500

    $res = $this->withHeaders(bearer($this->fd))->getJson('/api/v1/orders/lookup?q=' . $o['order_code'])->assertOk();

    expect($res->json('data.results.0.invoice.balance_amount'))->toEqual(1500);
});

it('returns the ready rack slot only for a ready item', function () {
    $o = lookupReadyOrder($this);
    OrderItem::query()->where('id', $o['item_id'])->update(['state' => 'ready_for_delivery']);
    RackSlot::factory()->for($this->branch)->create([
        'slot_code' => 'R2-S4',
        'current_order_item_id' => $o['item_id'],
        'occupied_at' => now(),
    ]);

    $res = $this->withHeaders(bearer($this->fd))->getJson('/api/v1/rack/search?q=' . $o['order_code'])->assertOk();

    expect($res->json('data.results.0.ready'))->toBeTrue()
        ->and(collect($res->json('data.results.0.rack_slots'))->pluck('slot_code'))->toContain('R2-S4');
});

it('returns status but no rack slot for a not-ready order', function () {
    $o = lookupReadyOrder($this); // item still draft → not ready

    $res = $this->withHeaders(bearer($this->fd))->getJson('/api/v1/rack/search?q=' . $o['order_code'])->assertOk();

    expect($res->json('data.results.0.ready'))->toBeFalse()
        ->and($res->json('data.results.0.rack_slots'))->toBe([]);
});

it('lets Front Desk move a production card (single-operator mode)', function () {
    $o = lookupReadyOrder($this); // confirmed → item staged at fabric_allocated

    $this->withHeaders(bearer($this->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson("/api/v1/production/items/{$o['item_id']}/transition", ['to' => 'cutting'])
        ->assertOk()
        ->assertJsonPath('data.state', 'cutting');
});

it('still blocks Front Desk from the finance dashboard', function () {
    $this->withHeaders(bearer($this->fd))->getJson('/api/v1/finance/dashboard/summary')->assertStatus(403);
});

it('does not return orders from another branch', function () {
    lookupReadyOrder($this); // HQ order

    $other = makeBranch(['code' => 'OTHER']);
    $otherUser = makeUser($other, 'Front Desk');
    $otherCustomer = Customer::factory()->for($other)->create();
    $otherVersion = approvedVersionFor($other, $otherCustomer);
    $res = $this->withHeaders(bearer($otherUser) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($otherCustomer->id, $otherVersion->id))->assertCreated();
    $otherCode = $res->json('data.order_code');

    $look = $this->withHeaders(bearer($this->fd))->getJson('/api/v1/orders/lookup?q=' . $otherCode)->assertOk();
    expect($look->json('data.results'))->toBe([]);
});
