<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Services\BalanceService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->fd = makeUser($this->branch, 'Front Desk');
    $this->customer = Customer::factory()->for($this->branch)->create();
    $this->version = approvedVersionFor($this->branch, $this->customer);
});

/**
 * Intake order with N boxed + PDF'd sub-orders, ready to confirm.
 *
 * @return array{0:int,1:array<int,int>} [orderId, itemIds]
 */
function pricedOrder($ctx, int $count = 2): array
{
    $items = [];
    for ($i = 0; $i < $count; $i++) {
        $items[] = ['product_type' => 'shirt', 'quantity' => 1, 'measurement_version_id' => $ctx->version->id];
    }

    $res = test()->withHeaders(bearer($ctx->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($ctx->customer->id, $ctx->version->id, [
            'items' => $items,
            'lifecycle_status' => 'intake_preparation',
        ]))->assertCreated();

    $orderId = $res->json('data.id');
    $itemIds = collect($res->json('data.items'))->pluck('id')->all();

    foreach ($itemIds as $itemId) {
        test()->withHeaders(bearer($ctx->fd))->getJson("/api/v1/orders/{$orderId}/items/{$itemId}/job-card")->assertCreated();
    }

    return [$orderId, $itemIds];
}

it('creates one priced invoice line per sub-order', function () {
    [$orderId, $items] = pricedOrder($this, 2);

    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/confirm", [
        'pricing' => ['lines' => [
            ['order_item_id' => $items[0], 'base_price' => 1500, 'discount_amount' => 100, 'gst_rate' => 0],
            ['order_item_id' => $items[1], 'base_price' => 1000, 'gst_rate' => 0],
        ]],
        'payment' => ['advance_amount' => 500, 'method' => 'cash'],
    ])->assertOk();

    $invoice = Invoice::query()->where('order_id', $orderId)->first();
    // (1500-100) + 1000 = 1400 + 1000 = 2400
    expect($invoice->lines()->count())->toBe(2)
        ->and($invoice->total_paise)->toBe(240000);
});

it('computes GST server-side for 0/5/12/18 rates', function () {
    [$orderId, $items] = pricedOrder($this, 4);

    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/confirm", [
        'pricing' => ['lines' => [
            ['order_item_id' => $items[0], 'base_price' => 1000, 'gst_rate' => 0],
            ['order_item_id' => $items[1], 'base_price' => 1000, 'gst_rate' => 5],
            ['order_item_id' => $items[2], 'base_price' => 1000, 'gst_rate' => 12],
            ['order_item_id' => $items[3], 'base_price' => 1000, 'gst_rate' => 18],
        ]],
    ])->assertOk();

    $invoice = Invoice::query()->where('order_id', $orderId)->first();
    // taxable 4000; tax = 0 + 50 + 120 + 180 = 350
    expect($invoice->subtotal_paise)->toBe(400000)
        ->and($invoice->cgst_paise + $invoice->sgst_paise + $invoice->igst_paise)->toBe(35000)
        ->and($invoice->total_paise)->toBe(435000);
});

it('rejects a discount greater than the line price + charges (422)', function () {
    [$orderId, $items] = pricedOrder($this, 1);

    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/confirm", [
        'pricing' => ['lines' => [
            ['order_item_id' => $items[0], 'base_price' => 1000, 'discount_amount' => 1500, 'gst_rate' => 0],
        ]],
    ])->assertStatus(422);

    expect(Invoice::query()->count())->toBe(0);
});

it('returns grand total and balance from per-line pricing', function () {
    [$orderId, $items] = pricedOrder($this, 2);

    $res = $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/confirm", [
        'pricing' => ['lines' => [
            ['order_item_id' => $items[0], 'base_price' => 1000, 'gst_rate' => 5],
            ['order_item_id' => $items[1], 'base_price' => 1000, 'gst_rate' => 5],
        ]],
        'payment' => ['advance_amount' => 500, 'method' => 'upi', 'reference' => 'U1'],
    ])->assertOk()->assertJsonPath('data.invoice.status', 'partially_paid');

    $invoice = Invoice::query()->where('order_id', $orderId)->first();
    // taxable 2000, gst 100, total 2100; advance 500 → balance 1600
    expect($invoice->total_paise)->toBe(210000)
        ->and(app(BalanceService::class)->outstandingForInvoice($invoice))->toBe(160000);
});

it('rejects an advance greater than the grand total (422) and rolls back', function () {
    [$orderId, $items] = pricedOrder($this, 1);

    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/confirm", [
        'pricing' => ['lines' => [['order_item_id' => $items[0], 'base_price' => 1000, 'gst_rate' => 0]]],
        'payment' => ['advance_amount' => 1500, 'method' => 'cash'],
    ])->assertStatus(422)->assertJsonPath('code', 'PAYMENT_EXCEEDS_BALANCE');

    expect(Invoice::query()->count())->toBe(0);
});

it('requires exactly one pricing line per sub-order (422)', function () {
    [$orderId, $items] = pricedOrder($this, 2);

    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/confirm", [
        'pricing' => ['lines' => [['order_item_id' => $items[0], 'base_price' => 1000, 'gst_rate' => 0]]],
    ])->assertStatus(422)->assertJsonPath('code', 'PRICING_LINES_INCOMPLETE');
});

it('does not duplicate invoice or payment on re-confirm with per-line pricing', function () {
    [$orderId, $items] = pricedOrder($this, 1);
    $payload = [
        'pricing' => ['lines' => [['order_item_id' => $items[0], 'base_price' => 2000, 'gst_rate' => 0]]],
        'payment' => ['advance_amount' => 500, 'method' => 'cash'],
    ];

    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/confirm", $payload)->assertOk();
    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/confirm", $payload)->assertOk();

    expect(Invoice::query()->where('order_id', $orderId)->count())->toBe(1)
        ->and(Payment::query()->count())->toBe(1);
});

it('still supports the legacy single-total pricing path', function () {
    [$orderId] = pricedOrder($this, 2);

    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/confirm", [
        'pricing' => ['total_amount' => 3000],
        'payment' => ['advance_amount' => 1000, 'method' => 'cash'],
    ])->assertOk();

    $invoice = Invoice::query()->where('order_id', $orderId)->first();
    expect($invoice->lines()->count())->toBe(2)
        ->and($invoice->total_paise)->toBe(300000);
});

it('keeps the invoice immutable after confirmation', function () {
    [$orderId, $items] = pricedOrder($this, 1);

    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/confirm", [
        'pricing' => ['lines' => [['order_item_id' => $items[0], 'base_price' => 1000, 'gst_rate' => 0]]],
    ])->assertOk();

    $invoice = Invoice::query()->where('order_id', $orderId)->first();

    expect(fn () => $invoice->update(['total_paise' => 1]))->toThrow(QueryException::class);
});
