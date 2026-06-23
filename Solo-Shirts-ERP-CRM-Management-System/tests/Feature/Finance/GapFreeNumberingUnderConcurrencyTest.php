<?php

declare(strict_types=1);

use App\Modules\Finance\Services\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

it('issues a gap-free, monotonic sequence with no duplicates', function () {
    $service = app(InvoiceNumberService::class);
    $fy = 2026;

    $numbers = [];
    for ($i = 0; $i < 100; $i++) {
        $invoiceNo = $service->nextInvoiceNumber($this->branch, $fy);
        // Trailing 5-digit counter, e.g. SSI-HQ-INV-2026-00042.
        $numbers[] = (int) substr($invoiceNo, -5);
    }

    expect($numbers)->toHaveCount(100)
        ->and(array_unique($numbers))->toHaveCount(100)
        ->and(min($numbers))->toBe(1)
        ->and(max($numbers))->toBe(100)
        ->and($numbers)->toBe(range(1, 100));

    $last = DB::table('invoice_sequences')
        ->where('branch_id', $this->branch->id)
        ->where('fiscal_year', $fy)
        ->value('last_number');
    expect((int) $last)->toBe(100);
});
