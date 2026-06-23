<?php

declare(strict_types=1);

use App\Modules\Finance\Services\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

it('restarts the counter at 1 for a new fiscal year (Apr 1 IST)', function () {
    $service = app(InvoiceNumberService::class);

    // Late March 2026 → FY 2025.
    $this->travelTo(Carbon::parse('2026-03-30 12:00', 'Asia/Kolkata'));
    $fy2025 = $service->fiscalYear();
    expect($fy2025)->toBe(2025);

    $first2025 = $service->nextInvoiceNumber($this->branch, $fy2025);
    $second2025 = $service->nextInvoiceNumber($this->branch, $fy2025);
    expect((int) substr($first2025, -5))->toBe(1)
        ->and((int) substr($second2025, -5))->toBe(2)
        ->and($first2025)->toContain('-2025-');

    // Cross into April 2026 → FY 2026: a brand-new sequence starting at 1.
    $this->travelTo(Carbon::parse('2026-04-02 12:00', 'Asia/Kolkata'));
    $fy2026 = $service->fiscalYear();
    expect($fy2026)->toBe(2026);

    $first2026 = $service->nextInvoiceNumber($this->branch, $fy2026);
    expect((int) substr($first2026, -5))->toBe(1)
        ->and($first2026)->toContain('-2026-');
});
