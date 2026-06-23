<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->accountant = makeUser($this->branch, 'Accountant');
});

it('numbers credit notes gap-free per (branch, fiscal year)', function () {
    $invoice = makeInvoice($this->branch, null, ['total_paise' => 100000]);

    $first = $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => 'cn-gap-1'])
        ->postJson("/api/v1/finance/invoices/{$invoice->id}/credit-note", [
            'reason' => 'Stitching defect refund', 'total' => 10000,
        ])
        ->assertCreated();

    $second = $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => 'cn-gap-2'])
        ->postJson("/api/v1/finance/invoices/{$invoice->id}/credit-note", [
            'reason' => 'Late delivery goodwill', 'total' => 5000,
        ])
        ->assertCreated();

    expect((int) substr((string) $first->json('data.credit_no'), -5))->toBe(1)
        ->and((int) substr((string) $second->json('data.credit_no'), -5))->toBe(2)
        ->and((string) $first->json('data.credit_no'))->toContain('-CN-');
});

it('rejects a credit note that exceeds the invoice total', function () {
    $invoice = makeInvoice($this->branch, null, ['total_paise' => 100000]);

    $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => 'cn-exceed-1'])
        ->postJson("/api/v1/finance/invoices/{$invoice->id}/credit-note", [
            'reason' => 'Too much', 'total' => 100001,
        ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'CREDIT_EXCEEDS_INVOICE');
});
