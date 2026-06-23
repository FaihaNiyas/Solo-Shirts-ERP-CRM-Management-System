<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Finance\Models\CreditNote;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Cross-module idempotency flow (Task 10 / QA-002). Drives the real
 * Idempotency-Key-protected write endpoints and asserts: replay returns the
 * same resource, a same-key/different-body retry is IDEMPOTENCY_CONFLICT, and a
 * missing key is rejected — all in the standard envelope with a request_id.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->frontDesk = makeUser($this->branch, 'Front Desk');
    $this->accountant = makeUser($this->branch, 'Accountant');
    $this->customer = Customer::factory()->for($this->branch)->create();
    $this->version = approvedVersionFor($this->branch, $this->customer);
});

it('replays an order create for a repeated key and conflicts on a different body', function () {
    $key = (string) Str::uuid();
    $payload = orderPayload($this->customer->id, $this->version->id);

    $first = $this->withHeaders(bearer($this->frontDesk) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/orders', $payload)
        ->assertCreated()
        ->assertJsonPath('request_id', fn ($id) => is_string($id) && $id !== '');

    $this->withHeaders(bearer($this->frontDesk) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/orders', $payload)
        ->assertCreated()
        ->assertJsonPath('data.id', $first->json('data.id'));

    $this->withHeaders(bearer($this->frontDesk) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/orders', orderPayload($this->customer->id, $this->version->id, ['source' => 'phone']))
        ->assertStatus(409)
        ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');

    expect(Order::query()->count())->toBe(1);
});

it('rejects an order create with no Idempotency-Key (400)', function () {
    $this->withHeaders(bearer($this->frontDesk))
        ->postJson('/api/v1/orders', orderPayload($this->customer->id, $this->version->id))
        ->assertStatus(400)
        ->assertJsonPath('code', 'IDEMPOTENCY_KEY_REQUIRED')
        ->assertJsonPath('request_id', fn ($id) => is_string($id) && $id !== '');
});

it('replays an invoice create and conflicts on a different body', function () {
    $order = deliverableOrder($this->branch);
    $key = (string) Str::uuid();

    $first = $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/finance/invoices', invoicePayload($order->id))
        ->assertCreated();

    $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/finance/invoices', invoicePayload($order->id))
        ->assertCreated()
        ->assertJsonPath('data.id', $first->json('data.id'));

    $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/finance/invoices', invoicePayload($order->id, ['discount_paise' => 9999]))
        ->assertStatus(409)
        ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');

    expect(Invoice::query()->count())->toBe(1);
});

it('replays a credit note for a repeated key', function () {
    $invoice = makeInvoice($this->branch, null, ['total_paise' => 100000]);
    $key = (string) Str::uuid();
    $payload = ['reason' => 'Defect refund', 'total' => 10000];

    $first = $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/finance/invoices/{$invoice->id}/credit-note", $payload)
        ->assertCreated();

    $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/finance/invoices/{$invoice->id}/credit-note", $payload)
        ->assertCreated()
        ->assertJsonPath('data.id', $first->json('data.id'));

    expect(CreditNote::query()->count())->toBe(1);
});

it('replays a payment for a repeated key', function () {
    $invoice = makeInvoice($this->branch, null, ['total_paise' => 100000]);
    $key = (string) Str::uuid();
    $payload = ['invoice_id' => $invoice->id, 'method' => 'cash', 'amount_paise' => 40000];

    $first = $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/finance/payments', $payload)
        ->assertCreated();

    $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/finance/payments', $payload)
        ->assertCreated()
        ->assertJsonPath('data.id', $first->json('data.id'));
});
