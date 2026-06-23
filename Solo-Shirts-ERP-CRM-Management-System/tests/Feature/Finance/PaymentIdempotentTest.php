<?php

declare(strict_types=1);

use App\Modules\Finance\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->accountant = makeUser($this->branch, 'Accountant');
});

it('returns the prior payment for a repeated Idempotency-Key', function () {
    $invoice = makeInvoice($this->branch, null, ['total_paise' => 100000]);
    $key = (string) Str::uuid();

    $body = ['invoice_id' => $invoice->id, 'method' => 'cash', 'amount_paise' => 50000];

    $first = $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/finance/payments', $body)
        ->assertCreated();

    $second = $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/finance/payments', $body)
        ->assertCreated();

    expect($second->json('data.id'))->toBe($first->json('data.id'));

    // Exactly one payment was recorded despite two requests.
    expect(Payment::query()->where('invoice_id', $invoice->id)->count())->toBe(1);
});

it('requires an Idempotency-Key header', function () {
    $invoice = makeInvoice($this->branch);

    $this->withHeaders(bearer($this->accountant))
        ->postJson('/api/v1/finance/payments', [
            'invoice_id' => $invoice->id, 'method' => 'cash', 'amount_paise' => 1000,
        ])
        ->assertStatus(400)
        ->assertJsonPath('code', 'IDEMPOTENCY_KEY_REQUIRED');
});

it('rejects a payment that exceeds the outstanding balance', function () {
    $invoice = makeInvoice($this->branch, null, ['total_paise' => 100000]);

    $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/finance/payments', [
            'invoice_id' => $invoice->id, 'method' => 'cash', 'amount_paise' => 100001,
        ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'PAYMENT_EXCEEDS_BALANCE');
});
