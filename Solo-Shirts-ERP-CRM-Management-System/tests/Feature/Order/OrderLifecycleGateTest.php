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
    $this->fd = makeUser($this->branch, 'Front Desk');
    $this->owner = makeUser($this->branch, 'Owner');
    $this->customer = Customer::factory()->for($this->branch)->create();
    $this->version = approvedVersionFor($this->branch, $this->customer);
});

/** Create an intake order via the wizard contract, with N items. */
function intakeOrder($ctx, int $count = 1): array
{
    $items = [];
    for ($i = 0; $i < $count; $i++) {
        $items[] = ['product_type' => 'shirt', 'quantity' => 1, 'measurement_version_id' => $ctx->version->id];
    }

    $res = test()->withHeaders(bearer($ctx->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($ctx->customer->id, $ctx->version->id, [
            'items' => $items,
            'lifecycle_status' => 'intake_preparation',
        ]))
        ->assertCreated();

    return [$res->json('data.id'), collect($res->json('data.items'))->pluck('id')->all()];
}

/** Generate the job-card PDF for an item (the prerequisite for confirm). */
function prepareItem($ctx, int $orderId, int $itemId): void
{
    test()->withHeaders(bearer($ctx->fd))
        ->getJson("/api/v1/orders/{$orderId}/items/{$itemId}/job-card")->assertCreated();
}

it('creates the intake order as intake_preparation, not order_received', function () {
    [$orderId] = intakeOrder($this);

    expect(Order::query()->find($orderId)->lifecycle_status)->toBe('intake_preparation');
});

it('hides intake order items from the cutting queue, then shows them after confirm', function () {
    [$orderId, $items] = intakeOrder($this, 1);
    $itemCode = OrderItem::query()->find($items[0])->item_code;

    // Intake → not in the cutting queue.
    $before = $this->withHeaders(bearer($this->owner))->getJson('/api/v1/cutting/queue')->assertOk()->json('data');
    expect(collect($before)->pluck('item_code'))->not->toContain($itemCode);

    // Prepare + confirm.
    prepareItem($this, $orderId, $items[0]);
    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/confirm")->assertOk();

    // Order Received → now in the cutting queue.
    $after = $this->withHeaders(bearer($this->owner))->getJson('/api/v1/cutting/queue')->assertOk()->json('data');
    expect(collect($after)->pluck('item_code'))->toContain($itemCode);
});

it('promotes an intake order to order_received on confirm', function () {
    [$orderId, $items] = intakeOrder($this, 2);
    foreach ($items as $itemId) {
        prepareItem($this, $orderId, $itemId);
    }

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/confirm")
        ->assertOk()
        ->assertJsonPath('data.order.lifecycle_status', 'order_received');

    expect(Order::query()->find($orderId)->lifecycle_status)->toBe('order_received');
});

it('blocks confirm when a sub-order has no job-card PDF (422 CONFIRM_MISSING_PDF)', function () {
    [$orderId] = intakeOrder($this, 1);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/confirm")
        ->assertStatus(422)
        ->assertJsonPath('code', 'CONFIRM_MISSING_PDF');
});
