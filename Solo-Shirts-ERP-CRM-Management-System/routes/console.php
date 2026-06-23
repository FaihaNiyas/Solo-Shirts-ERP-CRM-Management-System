<?php

declare(strict_types=1);

use App\Modules\Inventory\Jobs\LowStockAlertJob;
use App\Modules\Inventory\Jobs\ReconcileStockJob;
use App\Modules\Production\Jobs\NotifyDelayedItemsJob;
use App\Modules\Reporting\Jobs\DailyBranchStatsJob;
use App\Modules\Reporting\Jobs\OutstandingBalanceDigestJob;
use App\Modules\Reporting\Jobs\ProductionRollupJob;
use App\Modules\Reporting\Jobs\PruneOrphanQcPhotosJob;
use App\Modules\Reporting\Jobs\PruneStaleIdempotencyKeysJob;
use App\Modules\Reporting\Jobs\TailorDailyStatsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled jobs (Phase 17)
|--------------------------------------------------------------------------
| All times are IST. Nightly reconciliation/rollups, morning alerts, a weekly
| receivables digest, and hourly/daily housekeeping. Each job sets its own
| queue/retry policy.
*/

$ist = 'Asia/Kolkata';

Schedule::job(new ReconcileStockJob)->timezone($ist)->dailyAt('02:00');
Schedule::job(new ProductionRollupJob)->timezone($ist)->dailyAt('02:15');
Schedule::job(new DailyBranchStatsJob)->timezone($ist)->dailyAt('02:30');
Schedule::job(new TailorDailyStatsJob)->timezone($ist)->dailyAt('02:45');
Schedule::job(new PruneOrphanQcPhotosJob)->timezone($ist)->dailyAt('03:00');
Schedule::job(new LowStockAlertJob)->timezone($ist)->dailyAt('08:00');
Schedule::job(new NotifyDelayedItemsJob)->timezone($ist)->dailyAt('08:15');
Schedule::job(new OutstandingBalanceDigestJob)->timezone($ist)->weeklyOn(1, '09:00');
Schedule::job(new PruneStaleIdempotencyKeysJob)->hourly();
