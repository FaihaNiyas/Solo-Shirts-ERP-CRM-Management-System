<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Services;

use App\Modules\Reporting\Models\DailyBranchStat;
use Illuminate\Support\Facades\Cache;

/**
 * Serves the dashboard from the pre-computed daily_branch_stats rollup only —
 * never an OLTP join — so it stays fast on large datasets. Cached 60s per
 * branch+range.
 */
final class DashboardService
{
    public const CACHE_TTL_SECONDS = 60;

    /**
     * @return array{
     *     range_days: int,
     *     orders_received: int,
     *     orders_delivered: int,
     *     revenue_paise: int,
     *     defects: int
     * }
     */
    public function summary(?int $branchId, int $rangeDays = 30): array
    {
        $key = sprintf('dashboard.summary.%s.%d', $branchId ?? 'all', $rangeDays);

        /** @var array{range_days: int, orders_received: int, orders_delivered: int, revenue_paise: int, defects: int} $summary */
        $summary = Cache::remember($key, self::CACHE_TTL_SECONDS, function () use ($branchId, $rangeDays): array {
            $query = DailyBranchStat::query()
                ->where('on_date', '>=', now()->subDays($rangeDays)->toDateString());

            if ($branchId !== null) {
                $query->where('branch_id', $branchId);
            }

            /** @var object{orders_received: int|null, orders_delivered: int|null, revenue_paise: int|null, defects: int|null} $row */
            $row = $query->selectRaw(
                'COALESCE(SUM(orders_received),0) as orders_received, '
                . 'COALESCE(SUM(orders_delivered),0) as orders_delivered, '
                . 'COALESCE(SUM(revenue_paise),0) as revenue_paise, '
                . 'COALESCE(SUM(defects),0) as defects'
            )->first();

            return [
                'range_days' => $rangeDays,
                'orders_received' => (int) ($row->orders_received ?? 0),
                'orders_delivered' => (int) ($row->orders_delivered ?? 0),
                'revenue_paise' => (int) ($row->revenue_paise ?? 0),
                'defects' => (int) ($row->defects ?? 0),
            ];
        });

        return $summary;
    }
}
