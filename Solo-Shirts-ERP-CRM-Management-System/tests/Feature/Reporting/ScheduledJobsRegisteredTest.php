<?php

declare(strict_types=1);

use App\Modules\Inventory\Jobs\LowStockAlertJob;
use App\Modules\Inventory\Jobs\ReconcileStockJob;
use App\Modules\Reporting\Jobs\DailyBranchStatsJob;
use App\Modules\Reporting\Jobs\OutstandingBalanceDigestJob;
use App\Modules\Reporting\Jobs\ProductionRollupJob;
use App\Modules\Reporting\Jobs\PruneOrphanQcPhotosJob;
use App\Modules\Reporting\Jobs\PruneStaleIdempotencyKeysJob;
use App\Modules\Reporting\Jobs\TailorDailyStatsJob;
use Illuminate\Console\Scheduling\Schedule;

it('registers all expected scheduled jobs', function () {
    // Force the console schedule (routes/console.php) to load.
    $this->artisan('schedule:list')->assertExitCode(0);

    $schedule = app(Schedule::class);

    $summaries = collect($schedule->events())
        ->flatMap(fn ($event): array => array_filter([
            $event->description,
            $event->getSummaryForDisplay(),
        ]));

    $expected = [
        ReconcileStockJob::class,
        ProductionRollupJob::class,
        DailyBranchStatsJob::class,
        TailorDailyStatsJob::class,
        PruneOrphanQcPhotosJob::class,
        LowStockAlertJob::class,
        OutstandingBalanceDigestJob::class,
        PruneStaleIdempotencyKeysJob::class,
    ];

    foreach ($expected as $jobClass) {
        expect($summaries->contains($jobClass))->toBeTrue("schedule should include {$jobClass}");
    }
});
