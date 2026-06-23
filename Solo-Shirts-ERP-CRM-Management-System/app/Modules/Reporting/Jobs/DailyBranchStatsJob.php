<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Jobs;

use App\Modules\Delivery\Models\Delivery;
use App\Modules\Finance\Models\Payment;
use App\Modules\Identity\Models\Branch;
use App\Modules\Order\Models\Order;
use App\Modules\Production\Models\QcInspection;
use App\Modules\Reporting\Models\DailyBranchStat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Nightly rollup: for each branch, summarise a day's OLTP activity into one
 * daily_branch_stats row. The dashboard then reads only this rollup. The upsert
 * is idempotent on (branch_id, on_date), so re-running is safe.
 */
final class DailyBranchStatsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly ?string $onDate = null) {}

    public function handle(): void
    {
        $date = $this->onDate !== null ? Carbon::parse($this->onDate) : now()->subDay();

        $this->compute($date);
    }

    public function compute(Carbon $date): void
    {
        $day = $date->toDateString();

        Branch::query()->each(function (Branch $branch) use ($day): void {
            $ordersReceived = Order::query()->withoutGlobalScopes()
                ->where('branch_id', $branch->id)->whereDate('created_at', $day)->count();

            $ordersDelivered = Delivery::query()->withoutGlobalScopes()
                ->where('branch_id', $branch->id)
                ->where('status', Delivery::STATUS_DELIVERED)
                ->whereDate('completed_at', $day)->count();

            $revenue = (int) Payment::query()->withoutGlobalScopes()
                ->where('branch_id', $branch->id)->whereDate('paid_at', $day)->sum('amount_paise');

            $defects = QcInspection::query()->withoutGlobalScopes()
                ->where('branch_id', $branch->id)
                ->whereDate('inspected_at', $day)
                ->whereIn('disposition', [QcInspection::DISPOSITION_REWORK, QcInspection::DISPOSITION_REJECT])
                ->count();

            DailyBranchStat::query()->updateOrCreate(
                ['branch_id' => $branch->id, 'on_date' => $day],
                [
                    'orders_received' => $ordersReceived,
                    'orders_delivered' => $ordersDelivered,
                    'revenue_paise' => $revenue,
                    'defects' => $defects,
                ],
            );
        });
    }
}
