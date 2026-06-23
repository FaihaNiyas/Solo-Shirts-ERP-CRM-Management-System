<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Delivery\Services\RackSlotService;
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
 * Confirmed single-shirt order. When $ready, the shirt is moved to
 * ready_for_delivery and properly racked (slot + assignment ledger).
 *
 * @return array{0:int,1:int} [orderId, itemId]
 */
function handoverOrder($ctx, int $total, int $advance, bool $ready = true): array
{
    $customer = Customer::factory()->for($ctx->branch)->create();
    $version = approvedVersionFor($ctx->branch, $customer);

    $res = test()->withHeaders(bearer($ctx->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($customer->id, $version->id, [
            'items' => [['product_type' => 'shirt', 'quantity' => 1, 'measurement_version_id' => $version->id]],
            'lifecycle_status' => 'intake_preparation',
        ]))->assertCreated();

    $orderId = $res->json('data.id');
    $itemId = $res->json('data.items.0.id');

    test()->withHeaders(bearer($ctx->fd))->getJson("/api/v1/orders/{$orderId}/items/{$itemId}/job-card")->assertCreated();

    $confirm = ['pricing' => ['total_amount' => $total]];
    if ($advance > 0) {
        $confirm['payment'] = ['advance_amount' => $advance, 'method' => 'cash'];
    }
    test()->withHeaders(bearer($ctx->fd))->postJson("/api/v1/orders/{$orderId}/confirm", $confirm)->assertOk();

    if ($ready) {
        OrderItem::query()->where('id', $itemId)->update(['state' => 'ready_for_delivery']);
        RackSlot::factory()->for($ctx->branch)->create(['slot_code' => 'R1-S1', 'is_active' => true, 'current_order_item_id' => null]);
        app(RackSlotService::class)->assign($itemId, 'R1-S1', null);
    }

    return [$orderId, $itemId];
}

it('hands over a ready, fully-paid order and releases the rack slot', function () {
    [$orderId, $itemId] = handoverOrder($this, 1000, 1000, true);

    expect(RackSlot::query()->where('current_order_item_id', $itemId)->exists())->toBeTrue();

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/handover", ['mode' => 'pickup'])
        ->assertOk()
        ->assertJsonPath('data.status', 'delivered');

    expect((string) OrderItem::query()->find($itemId)->state)->toBe('delivered')
        ->and(RackSlot::query()->where('current_order_item_id', $itemId)->exists())->toBeFalse();
});

it('blocks handover when balance is pending (422 BALANCE_PENDING)', function () {
    [$orderId] = handoverOrder($this, 1000, 200, true);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/handover", ['mode' => 'pickup'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'BALANCE_PENDING');
});

it('allows handover after the balance is collected', function () {
    [$orderId, $itemId] = handoverOrder($this, 1000, 200, true); // balance 800

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/payments", ['amount' => 800, 'method' => 'cash'])->assertCreated();
    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/handover", ['mode' => 'pickup'])->assertOk();

    expect((string) OrderItem::query()->find($itemId)->state)->toBe('delivered');
});

it('blocks handover for a not-ready order (409 ORDER_NOT_READY)', function () {
    [$orderId] = handoverOrder($this, 1000, 1000, false);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/handover", ['mode' => 'pickup'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'ORDER_NOT_READY');
});

it('blocks handover for a cancelled order (409 ORDER_CANCELLED)', function () {
    [$orderId] = handoverOrder($this, 1000, 0, false);
    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/cancel", ['reason' => 'changed mind'])->assertOk();

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/handover", ['mode' => 'pickup'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'ORDER_CANCELLED');
});

it('blocks handover for an intake_preparation order (409 ORDER_NOT_CONFIRMED)', function () {
    $customer = Customer::factory()->for($this->branch)->create();
    $version = approvedVersionFor($this->branch, $customer);
    $res = $this->withHeaders(bearer($this->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($customer->id, $version->id, ['lifecycle_status' => 'intake_preparation']))
        ->assertCreated();

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$res->json('data.id')}/handover", ['mode' => 'pickup'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'ORDER_NOT_CONFIRMED');
});

it('reports eligibility blockers without changing anything', function () {
    [$orderId] = handoverOrder($this, 1000, 200, true); // balance pending

    $this->withHeaders(bearer($this->fd))
        ->getJson("/api/v1/orders/{$orderId}/handover-eligibility")
        ->assertOk()
        ->assertJsonPath('data.can_handover', false)
        ->assertJsonPath('data.ready', true);
});

it('forbids handover without the orders.handover permission (403)', function () {
    $staff = makeUser($this->branch, 'Measurement Staff'); // no orders.handover
    [$orderId] = handoverOrder($this, 1000, 1000, true);

    $this->withHeaders(bearer($staff))
        ->postJson("/api/v1/orders/{$orderId}/handover", ['mode' => 'pickup'])
        ->assertStatus(403);
});

it('does not grant Front Desk production transition permissions', function () {
    [, $itemId] = handoverOrder($this, 1000, 1000, true);

    $this->withHeaders(bearer($this->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson("/api/v1/production/items/{$itemId}/transition", ['to' => 'delivered'])
        ->assertStatus(403);
});

it('enforces branch scoping on handover', function () {
    $other = makeBranch(['code' => 'OTHER']);
    $otherUser = makeUser($other, 'Front Desk');
    $oc = Customer::factory()->for($other)->create();
    $ov = approvedVersionFor($other, $oc);
    $res = $this->withHeaders(bearer($otherUser) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($oc->id, $ov->id))->assertCreated();

    $this->withHeaders(bearer($this->fd))
        ->getJson("/api/v1/orders/{$res->json('data.id')}/handover-eligibility")
        ->assertStatus(404);
});
