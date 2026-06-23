<?php

declare(strict_types=1);

use App\Modules\Finance\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->accountant = makeUser($this->branch, 'Accountant');
});

// invoicePayload() is a shared helper defined in tests/Pest.php.

it('creates an invoice on the first request carrying an Idempotency-Key', function () {
    $order = deliverableOrder($this->branch);

    $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => 'inv-key-001'])
        ->postJson('/api/v1/finance/invoices', invoicePayload($order->id))
        ->assertCreated()
        ->assertJsonPath('data.status', 'issued');

    expect(Invoice::query()->count())->toBe(1);
});

it('replays the same invoice for a repeated Idempotency-Key + body', function () {
    $order = deliverableOrder($this->branch);
    $key = 'inv-key-002';
    $payload = invoicePayload($order->id);

    $first = $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/finance/invoices', $payload)->assertCreated();

    $second = $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/finance/invoices', $payload)->assertCreated();

    expect($second->json('data.id'))->toBe($first->json('data.id'))
        ->and($second->json('data.invoice_no'))->toBe($first->json('data.invoice_no'))
        ->and(Invoice::query()->count())->toBe(1);
});

it('returns 409 IDEMPOTENCY_CONFLICT for the same key but a different body', function () {
    $order = deliverableOrder($this->branch);
    $key = 'inv-key-003';

    $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/finance/invoices', invoicePayload($order->id))
        ->assertCreated();

    $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/finance/invoices', invoicePayload($order->id, ['discount_paise' => 6000]))
        ->assertStatus(409)
        ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT')
        ->assertJsonPath('request_id', fn ($id) => is_string($id) && $id !== '');

    expect(Invoice::query()->count())->toBe(1);
});
