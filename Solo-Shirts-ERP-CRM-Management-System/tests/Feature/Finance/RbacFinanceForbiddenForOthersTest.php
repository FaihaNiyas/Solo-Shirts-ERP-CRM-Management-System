<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->tailor = makeUser($this->branch, 'Tailor');
});

it('forbids a non-finance role from listing invoices', function () {
    $this->withHeaders(bearer($this->tailor))
        ->getJson('/api/v1/finance/invoices')
        ->assertForbidden();
});

it('forbids a non-finance role from recording a payment', function () {
    $invoice = makeInvoice($this->branch);

    $this->withHeaders(bearer($this->tailor) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/finance/payments', [
            'invoice_id' => $invoice->id, 'method' => 'cash', 'amount_paise' => 1000,
        ])
        ->assertForbidden();
});

it('forbids a non-finance role from viewing the dashboard', function () {
    $this->withHeaders(bearer($this->tailor))
        ->getJson('/api/v1/finance/dashboard/summary')
        ->assertForbidden();
});

it('allows an accountant through', function () {
    $accountant = makeUser($this->branch, 'Accountant');

    $this->withHeaders(bearer($accountant))
        ->getJson('/api/v1/finance/invoices')
        ->assertOk();
});
