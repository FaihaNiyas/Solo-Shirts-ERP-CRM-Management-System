<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Delivery\Services\RackSlotService;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentAllocation;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\PickupBatch;
use App\Modules\Printing\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->fd = makeUser($this->branch, 'Front Desk');
});

/**
 * Build a confirmed multi-shirt order with explicit per-shirt pricing + advance,
 * then move the items at $readyIdx to ready_for_delivery and rack them.
 *
 * @param  list<int>  $pricesRupees
 * @param  list<int>  $readyIdx
 * @return array{0:int,1:list<int>}
 */
function pickupOrder($ctx, array $pricesRupees, int $advanceRupees, array $readyIdx): array
{
    $customer = Customer::factory()->for($ctx->branch)->create();
    $version = approvedVersionFor($ctx->branch, $customer);

    $items = array_map(
        fn (): array => ['product_type' => 'shirt', 'quantity' => 1, 'measurement_version_id' => $version->id],
        $pricesRupees,
    );

    $res = test()->withHeaders(bearer($ctx->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($customer->id, $version->id, [
            'items' => $items,
            'lifecycle_status' => 'intake_preparation',
        ]))->assertCreated();

    $orderId = $res->json('data.id');
    $itemIds = collect($res->json('data.items'))->pluck('id')->all();

    foreach ($itemIds as $itemId) {
        test()->withHeaders(bearer($ctx->fd))->getJson("/api/v1/orders/{$orderId}/items/{$itemId}/job-card")->assertCreated();
    }

    $lines = [];
    foreach ($itemIds as $i => $itemId) {
        $lines[] = [
            'order_item_id' => $itemId, 'base_price' => $pricesRupees[$i],
            'style_charge' => 0, 'rush_charge' => 0, 'discount_amount' => 0, 'gst_rate' => 0,
        ];
    }
    $confirm = ['pricing' => ['lines' => $lines]];
    if ($advanceRupees > 0) {
        $confirm['payment'] = ['advance_amount' => $advanceRupees, 'method' => 'cash'];
    }
    test()->withHeaders(bearer($ctx->fd))->postJson("/api/v1/orders/{$orderId}/confirm", $confirm)->assertOk();

    foreach ($readyIdx as $i) {
        $itemId = $itemIds[$i];
        OrderItem::query()->where('id', $itemId)->update(['state' => 'ready_for_delivery']);
        $code = 'R1-S' . ($i + 1);
        RackSlot::factory()->for($ctx->branch)->create(['slot_code' => $code, 'is_active' => true, 'current_order_item_id' => null]);
        app(RackSlotService::class)->assign($itemId, $code, null);
    }

    return [$orderId, $itemIds];
}

function createBatch($ctx, int $orderId, array $itemIds)
{
    return test()->withHeaders(bearer($ctx->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson("/api/v1/orders/{$orderId}/pickup-batches", ['item_ids' => $itemIds, 'pickup_type' => 'counter_pickup']);
}

// 1 — one ready shirt creates a pickup batch with the right money figures.
it('creates a pickup batch for one ready item', function () {
    [$orderId, $itemIds] = pickupOrder($this, [1500, 1500, 2000], 1000, [0]);

    createBatch($this, $orderId, [$itemIds[0]])
        ->assertCreated()
        ->assertJsonPath('data.status', 'payment_pending')
        ->assertJsonPath('data.total_paise', 150000)     // item total 1500
        ->assertJsonPath('data.paid_paise', 30000)       // advance share 300
        ->assertJsonPath('data.balance_paise', 120000);  // due 1200
});

// 5 — a not-ready item cannot be added.
it('rejects a non-ready item', function () {
    [$orderId, $itemIds] = pickupOrder($this, [1500, 1500, 2000], 0, [0]);

    createBatch($this, $orderId, [$itemIds[1]])
        ->assertStatus(422)->assertJsonPath('code', 'PICKUP_ITEM_NOT_READY');
});

// 6 — an already-delivered item cannot be added.
it('rejects an already-delivered item', function () {
    [$orderId, $itemIds] = pickupOrder($this, [1500, 1500, 2000], 5000, [0]); // item0 fully paid
    $batch = createBatch($this, $orderId, [$itemIds[0]])->assertCreated()->json('data.id');
    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/pickup-batches/{$batch}/handover")->assertOk();

    createBatch($this, $orderId, [$itemIds[0]])
        ->assertStatus(422)->assertJsonPath('code', 'PICKUP_ITEM_DELIVERED');
});

// 7 — an item already in an active batch cannot join a second.
it('rejects an item already in an active batch', function () {
    [$orderId, $itemIds] = pickupOrder($this, [1500, 1500, 2000], 0, [0]);
    createBatch($this, $orderId, [$itemIds[0]])->assertCreated();

    createBatch($this, $orderId, [$itemIds[0]])
        ->assertStatus(409)->assertJsonPath('code', 'PICKUP_ITEM_IN_ACTIVE_BATCH');
});

// 8 — a batch payment cannot exceed the batch balance.
it('rejects a payment above the batch balance', function () {
    [$orderId, $itemIds] = pickupOrder($this, [1500, 1500, 2000], 1000, [0]);
    $batch = createBatch($this, $orderId, [$itemIds[0]])->assertCreated()->json('data.id'); // balance 1200

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/pickup-batches/{$batch}/payments", ['amount' => 2000, 'method' => 'cash'])
        ->assertStatus(422)->assertJsonPath('code', 'PICKUP_PAYMENT_EXCEEDS_BALANCE');
});

// 9 — a batch payment creates a Payment row + selected-item allocations.
it('records a payment and selected-item allocations for the batch', function () {
    [$orderId, $itemIds] = pickupOrder($this, [1500, 1500, 2000], 1000, [0]);
    $batch = createBatch($this, $orderId, [$itemIds[0]])->assertCreated()->json('data.id');

    $before = Payment::query()->count();
    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/pickup-batches/{$batch}/payments", ['amount' => 1200, 'method' => 'cash'])
        ->assertCreated()->assertJsonPath('data.status', 'paid')->assertJsonPath('data.balance_paise', 0);

    expect(Payment::query()->count())->toBe($before + 1)
        ->and((int) PaymentAllocation::query()->where('pickup_batch_id', $batch)
            ->where('allocation_type', PaymentAllocation::TYPE_SELECTED_ITEM_BALANCE)
            ->where('order_item_id', $itemIds[0])->sum('amount_paise'))->toBe(120000);
});

// 10–14 — pay then handover affects only the selected item; parent partially delivered.
it('hands over only the selected item and leaves siblings untouched', function () {
    [$orderId, $itemIds] = pickupOrder($this, [1500, 1500, 2000], 1000, [0, 1, 2]);
    $batch = createBatch($this, $orderId, [$itemIds[0]])->assertCreated()->json('data.id');
    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/pickup-batches/{$batch}/payments", ['amount' => 1200, 'method' => 'cash'])->assertCreated();

    expect(RackSlot::query()->where('current_order_item_id', $itemIds[0])->exists())->toBeTrue();

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/pickup-batches/{$batch}/handover")
        ->assertOk()
        ->assertJsonPath('data.order_progress.aggregate_status', 'partially_delivered');

    expect((string) OrderItem::query()->find($itemIds[0])->state)->toBe('delivered')                 // 11
        ->and(RackSlot::query()->where('current_order_item_id', $itemIds[0])->exists())->toBeFalse()  // 12
        ->and((string) OrderItem::query()->find($itemIds[1])->state)->toBe('ready_for_delivery')      // 13
        ->and((string) OrderItem::query()->find($itemIds[2])->state)->toBe('ready_for_delivery');
});

// 15 — a ready item may sit in the rack with no payment / no batch.
it('lets a ready item wait in the rack with no payment', function () {
    [, $itemIds] = pickupOrder($this, [1500, 1500, 2000], 0, [0]);

    expect((string) OrderItem::query()->find($itemIds[0])->state)->toBe('ready_for_delivery')
        ->and(RackSlot::query()->where('current_order_item_id', $itemIds[0])->exists())->toBeTrue()
        ->and(PickupBatch::query()->count())->toBe(0)
        ->and(Payment::query()->count())->toBe(0);
});

// 16–17 — wait for all, pay full order balance, full-order handover delivers all.
it('supports the wait-for-all flow: pay full balance then hand over everything', function () {
    [$orderId, $itemIds] = pickupOrder($this, [1500, 1500, 2000], 1000, [0, 1, 2]); // order balance 4000

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/payments", ['amount' => 4000, 'method' => 'cash'])->assertCreated();

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/handover", ['mode' => 'pickup'])
        ->assertOk()->assertJsonPath('data.status', 'delivered');

    foreach ($itemIds as $id) {
        expect((string) OrderItem::query()->find($id)->state)->toBe('delivered');
    }
});

// 18 — duplicate batch handover is blocked.
it('blocks a duplicate batch handover', function () {
    [$orderId, $itemIds] = pickupOrder($this, [1500, 1500, 2000], 5000, [0]); // item0 fully paid
    $batch = createBatch($this, $orderId, [$itemIds[0]])->assertCreated()->json('data.id');
    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/pickup-batches/{$batch}/handover")->assertOk();

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/pickup-batches/{$batch}/handover")
        ->assertStatus(409)->assertJsonPath('code', 'PICKUP_ALREADY_HANDED_OVER');
});

// 19 — idempotent batch payment does not double-charge.
it('does not double-charge a replayed batch payment', function () {
    [$orderId, $itemIds] = pickupOrder($this, [1500, 1500, 2000], 1000, [0]);
    $batch = createBatch($this, $orderId, [$itemIds[0]])->assertCreated()->json('data.id'); // balance 1200
    $key = (string) Str::uuid();
    $before = Payment::query()->count();

    foreach ([1, 2] as $_) {
        $this->withHeaders(bearer($this->fd) + ['Idempotency-Key' => $key])
            ->postJson("/api/v1/orders/{$orderId}/pickup-batches/{$batch}/payments", ['amount' => 600, 'method' => 'cash'])
            ->assertCreated()->assertJsonPath('data.balance_paise', 60000); // 1200 - 600, once only
    }

    expect(Payment::query()->count())->toBe($before + 1);
});

// 20 — pickup receipt is filed against the batch (selected items only).
it('files a pickup receipt referencing the batch', function () {
    [$orderId, $itemIds] = pickupOrder($this, [1500, 1500, 2000], 5000, [0]);
    $batch = createBatch($this, $orderId, [$itemIds[0]])->assertCreated()->json('data.id');
    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/pickup-batches/{$batch}/handover")->assertOk();

    $this->withHeaders(bearer($this->fd))
        ->getJson("/api/v1/orders/{$orderId}/pickup-batches/{$batch}/receipt")
        ->assertCreated();

    expect(Document::query()->where('kind', 'pickup_receipt')
        ->where('reference_type', PickupBatch::class)->where('reference_id', $batch)->exists())->toBeTrue();
});

// 21 — cross-branch pickup batch is 404.
it('blocks cross-branch pickup batch creation', function () {
    $other = makeBranch(['code' => 'OTHER']);
    $otherFd = makeUser($other, 'Front Desk');
    [$orderId, $itemIds] = pickupOrder((object) ['branch' => $other, 'fd' => $otherFd], [1500], 0, [0]);

    $this->withHeaders(bearer($this->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson("/api/v1/orders/{$orderId}/pickup-batches", ['item_ids' => [$itemIds[0]]])
        ->assertStatus(404);
});

// 23 — a production user cannot create / pay / hand over a pickup batch.
it('forbids a production user from pickup actions (403)', function () {
    $tailor = makeUser($this->branch, 'Tailor');
    [$orderId, $itemIds] = pickupOrder($this, [1500, 1500, 2000], 0, [0]);

    test()->withHeaders(bearer($tailor) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson("/api/v1/orders/{$orderId}/pickup-batches", ['item_ids' => [$itemIds[0]]])
        ->assertStatus(403);
});

// 24 — no Front Desk path hands over an unpaid item in V1.
it('blocks handover of an unpaid pickup batch', function () {
    [$orderId, $itemIds] = pickupOrder($this, [1500, 1500, 2000], 1000, [0]); // balance 1200
    $batch = createBatch($this, $orderId, [$itemIds[0]])->assertCreated()->json('data.id');

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/pickup-batches/{$batch}/handover")
        ->assertStatus(422)->assertJsonPath('code', 'PICKUP_BALANCE_PENDING');

    expect((string) OrderItem::query()->find($itemIds[0])->state)->toBe('ready_for_delivery');
});
