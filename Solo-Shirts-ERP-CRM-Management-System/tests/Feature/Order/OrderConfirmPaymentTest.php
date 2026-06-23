<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Services\BalanceService;
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

/** Create an intake order and make every sub-order confirm-ready (box + PDF). */
function readyIntakeOrder($ctx, int $count = 2): int
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

    $orderId = $res->json('data.id');

    foreach (collect($res->json('data.items'))->pluck('id') as $itemId) {
        test()->withHeaders(bearer($ctx->fd))
            ->getJson("/api/v1/orders/{$orderId}/items/{$itemId}/job-card")->assertCreated();
    }

    return $orderId;
}

it('creates an invoice and records the advance on confirm (Front Desk)', function () {
    $orderId = readyIntakeOrder($this, 2);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/confirm", [
            'pricing' => ['total_amount' => 4500],
            'payment' => ['advance_amount' => 2000, 'method' => 'upi', 'reference' => 'UPI123456'],
        ])
        ->assertOk()
        ->assertJsonPath('data.order.lifecycle_status', 'order_received')
        ->assertJsonPath('data.invoice.status', 'partially_paid')
        ->assertJsonPath('data.payment.method', 'upi');

    $invoice = Invoice::query()->where('order_id', $orderId)->first();
    expect($invoice)->not->toBeNull()
        ->and($invoice->total_paise)->toBe(450000)
        ->and($invoice->lines()->count())->toBe(2);

    $payment = Payment::query()->where('invoice_id', $invoice->id)->first();
    expect($payment->amount_paise)->toBe(200000)
        ->and($payment->method)->toBe('upi')
        ->and(app(BalanceService::class)->outstandingForInvoice($invoice))->toBe(250000);
});

it('creates an invoice but no payment when advance is zero', function () {
    $orderId = readyIntakeOrder($this, 1);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/confirm", [
            'pricing' => ['total_amount' => 3000],
            'payment' => ['advance_amount' => 0],
        ])
        ->assertOk()
        ->assertJsonPath('data.payment', null)
        ->assertJsonPath('data.invoice.status', 'issued');

    $invoice = Invoice::query()->where('order_id', $orderId)->first();
    expect($invoice->total_paise)->toBe(300000)
        ->and(Payment::query()->count())->toBe(0);
});

it('rejects an advance greater than the total (422)', function () {
    $orderId = readyIntakeOrder($this, 1);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/confirm", [
            'pricing' => ['total_amount' => 1000],
            'payment' => ['advance_amount' => 1500, 'method' => 'cash'],
        ])
        ->assertStatus(422);

    expect(Invoice::query()->count())->toBe(0);
});

it('requires a payment method when an advance is recorded (422)', function () {
    $orderId = readyIntakeOrder($this, 1);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/confirm", [
            'pricing' => ['total_amount' => 1000],
            'payment' => ['advance_amount' => 500],
        ])
        ->assertStatus(422);
});

it('does not duplicate invoice or payment on re-confirm', function () {
    $orderId = readyIntakeOrder($this, 2);
    $payload = [
        'pricing' => ['total_amount' => 4000],
        'payment' => ['advance_amount' => 1000, 'method' => 'cash'],
    ];

    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/confirm", $payload)->assertOk();
    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/confirm", $payload)->assertOk();

    expect(Invoice::query()->where('order_id', $orderId)->count())->toBe(1)
        ->and(Payment::query()->count())->toBe(1);
});

it('forbids Front Desk from the finance dashboard and credit notes (403)', function () {
    $this->withHeaders(bearer($this->fd))
        ->getJson('/api/v1/finance/dashboard/summary')
        ->assertStatus(403);

    $orderId = readyIntakeOrder($this, 1);
    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/confirm", ['pricing' => ['total_amount' => 1000]])->assertOk();
    $invoiceId = Invoice::query()->where('order_id', $orderId)->first()->id;

    $this->withHeaders(bearer($this->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson("/api/v1/finance/invoices/{$invoiceId}/credit-note", ['reason' => 'test', 'total' => 100])
        ->assertStatus(403);
});

it('excludes intake orders from finance dashboard counts (only confirmed are invoiced)', function () {
    $admin = makeUser($this->branch, 'Admin');

    // An intake order, prepared but NOT confirmed → no invoice.
    readyIntakeOrder($this, 1);

    // A confirmed order → exactly one invoice.
    $confirmedId = readyIntakeOrder($this, 1);
    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$confirmedId}/confirm", ['pricing' => ['total_amount' => 1000]])->assertOk();

    $this->withHeaders(bearer($admin))
        ->getJson('/api/v1/finance/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('data.invoice_count', 1);
});
