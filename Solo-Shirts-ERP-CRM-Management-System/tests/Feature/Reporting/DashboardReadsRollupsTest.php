<?php

declare(strict_types=1);

use App\Modules\Reporting\Models\DailyBranchStat;
use App\Modules\Reporting\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

it('serves the dashboard purely from the rollup table, never OLTP joins', function () {
    DailyBranchStat::factory()->for($this->branch)->create([
        'on_date' => now()->toDateString(),
        'orders_received' => 10,
        'orders_delivered' => 4,
        'revenue_paise' => 250000,
        'defects' => 2,
    ]);

    Cache::flush();
    DB::flushQueryLog();
    DB::enableQueryLog();

    $summary = app(DashboardService::class)->summary($this->branch->id);

    $queries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    // The rollup is read…
    expect($queries->contains(fn (string $q): bool => str_contains($q, 'daily_branch_stats')))->toBeTrue();

    // …and no OLTP table is touched.
    foreach (['order_items', ' orders ', 'invoices', 'payments', 'qc_inspections'] as $oltp) {
        expect($queries->contains(fn (string $q): bool => str_contains($q, $oltp)))->toBeFalse();
    }

    expect($summary['orders_received'])->toBe(10)
        ->and($summary['revenue_paise'])->toBe(250000);
});
