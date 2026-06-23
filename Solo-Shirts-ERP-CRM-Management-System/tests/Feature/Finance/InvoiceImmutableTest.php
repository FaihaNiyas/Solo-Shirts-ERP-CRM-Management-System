<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

it('rejects any change to an issued invoice number at the database level', function () {
    $invoice = makeInvoice($this->branch);

    expect(fn () => DB::table('invoices')->where('id', $invoice->id)->update(['invoice_no' => 'TAMPERED']))
        ->toThrow(QueryException::class);

    expect($invoice->fresh()->invoice_no)->toBe($invoice->invoice_no);
});

it('rejects any change to the invoice total at the database level', function () {
    $invoice = makeInvoice($this->branch);

    expect(fn () => DB::table('invoices')->where('id', $invoice->id)->update(['total_paise' => 1]))
        ->toThrow(QueryException::class);
});

it('still allows status updates so the payment reconciler can work', function () {
    $invoice = makeInvoice($this->branch);

    $invoice->update(['status' => 'paid']);

    expect($invoice->fresh()->status)->toBe('paid');
});
