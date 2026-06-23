<?php

declare(strict_types=1);

use App\Modules\Finance\Models\CreditNote;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->accountant = makeUser($this->branch, 'Accountant');
    $this->invoice = makeInvoice($this->branch, null, ['total_paise' => 100000]);
});

it('creates a credit note on the first request carrying an Idempotency-Key', function () {
    $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => 'cn-key-001'])
        ->postJson("/api/v1/finance/invoices/{$this->invoice->id}/credit-note", [
            'reason' => 'Stitching defect refund', 'total' => 10000,
        ])
        ->assertCreated();

    expect(CreditNote::query()->count())->toBe(1);
});

it('replays the same credit note for a repeated Idempotency-Key + body', function () {
    $key = 'cn-key-002';
    $payload = ['reason' => 'Stitching defect refund', 'total' => 10000];

    $first = $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/finance/invoices/{$this->invoice->id}/credit-note", $payload)
        ->assertCreated();

    $second = $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/finance/invoices/{$this->invoice->id}/credit-note", $payload)
        ->assertCreated();

    expect($second->json('data.id'))->toBe($first->json('data.id'))
        ->and($second->json('data.credit_no'))->toBe($first->json('data.credit_no'))
        ->and(CreditNote::query()->count())->toBe(1);
});

it('returns 409 IDEMPOTENCY_CONFLICT for the same key but a different body', function () {
    $key = 'cn-key-003';

    $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/finance/invoices/{$this->invoice->id}/credit-note", [
            'reason' => 'Stitching defect refund', 'total' => 10000,
        ])
        ->assertCreated();

    $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/finance/invoices/{$this->invoice->id}/credit-note", [
            'reason' => 'Late delivery goodwill', 'total' => 5000,
        ])
        ->assertStatus(409)
        ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT')
        ->assertJsonPath('request_id', fn ($id) => is_string($id) && $id !== '');

    expect(CreditNote::query()->count())->toBe(1);
});
