<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Finance\Models\Invoice;
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

/** Confirmed order (invoice created) with a known total and advance, in rupees. */
function payConfirmedOrder($ctx, int $total, int $advance, int $count = 1): int
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

    $confirm = ['pricing' => ['total_amount' => $total]];
    if ($advance > 0) {
        $confirm['payment'] = ['advance_amount' => $advance, 'method' => 'cash'];
    }
    test()->withHeaders(bearer($ctx->fd))->postJson("/api/v1/orders/{$orderId}/confirm", $confirm)->assertOk();

    return $orderId;
}

it('lets Front Desk record a balance payment that updates the invoice', function () {
    $orderId = payConfirmedOrder($this, 4500, 2000);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/payments", ['amount' => 1000, 'method' => 'upi', 'reference' => 'UPI1'])
        ->assertCreated()
        ->assertJsonPath('data.invoice.status', 'partially_paid')
        ->assertJsonPath('data.payment.method', 'upi');

    $invoice = Invoice::query()->where('order_id', $orderId)->first();
    expect(app(BalanceService::class)->outstandingForInvoice($invoice))->toBe(150000);
});

it('rejects a balance payment greater than the outstanding (422)', function () {
    $orderId = payConfirmedOrder($this, 1000, 200);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/payments", ['amount' => 900, 'method' => 'cash'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'PAYMENT_EXCEEDS_BALANCE');
});

it('rejects a non-positive amount (422)', function () {
    $orderId = payConfirmedOrder($this, 1000, 0);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/payments", ['amount' => 0, 'method' => 'cash'])
        ->assertStatus(422);
});

it('requires a payment method (422)', function () {
    $orderId = payConfirmedOrder($this, 1000, 0);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/payments", ['amount' => 100])
        ->assertStatus(422);
});

it('rejects further payment once the invoice is fully paid (422)', function () {
    $orderId = payConfirmedOrder($this, 1000, 1000); // fully paid at confirm

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/payments", ['amount' => 100, 'method' => 'cash'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'PAYMENT_EXCEEDS_BALANCE');
});

it('blocks balance payment on an intake_preparation order (409)', function () {
    $res = $this->withHeaders(bearer($this->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($this->customer->id, $this->version->id, [
            'lifecycle_status' => 'intake_preparation',
        ]))
        ->assertCreated();
    $orderId = $res->json('data.id');

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/payments", ['amount' => 100, 'method' => 'cash'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'ORDER_NOT_CONFIRMED');
});

it('records append-only payments that drive the invoice to paid, with history', function () {
    $orderId = payConfirmedOrder($this, 1000, 400); // balance 600

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/payments", ['amount' => 600, 'method' => 'cash'])
        ->assertCreated()
        ->assertJsonPath('data.invoice.status', 'paid');

    $invoice = Invoice::query()->where('order_id', $orderId)->first();
    expect($invoice->payments()->count())->toBe(2)
        ->and(app(BalanceService::class)->outstandingForInvoice($invoice))->toBe(0);

    $this->withHeaders(bearer($this->fd))
        ->getJson("/api/v1/orders/{$orderId}/payments")
        ->assertOk()
        ->assertJsonCount(2, 'data.payments');
});

it('still forbids Front Desk from broad finance endpoints (403)', function () {
    $this->withHeaders(bearer($this->fd))
        ->getJson('/api/v1/finance/dashboard/summary')
        ->assertStatus(403);

    // The broad /finance/payments endpoint stays closed (FD lacks finance.payment.record).
    // Use a real invoice so the request clears validation and reaches authorization.
    $orderId = payConfirmedOrder($this, 1000, 0);
    $invoiceId = Invoice::query()->where('order_id', $orderId)->first()->id;

    $this->withHeaders(bearer($this->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/finance/payments', ['invoice_id' => $invoiceId, 'method' => 'cash', 'amount_paise' => 100])
        ->assertStatus(403);
});

it('does not duplicate a balance payment on idempotent retry', function () {
    $orderId = payConfirmedOrder($this, 2000, 0);
    $key = (string) Str::uuid();
    $payload = ['amount' => 500, 'method' => 'cash'];

    $this->withHeaders(bearer($this->fd) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/orders/{$orderId}/payments", $payload)->assertCreated();
    $this->withHeaders(bearer($this->fd) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/orders/{$orderId}/payments", $payload)->assertCreated();

    $invoice = Invoice::query()->where('order_id', $orderId)->first();
    expect($invoice->payments()->count())->toBe(1);
});
