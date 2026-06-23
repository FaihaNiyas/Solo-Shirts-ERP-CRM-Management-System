<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Reports;

use App\Modules\Order\Models\OrderItem;
use App\Modules\Reporting\Reports\Concerns\FiltersByDateRange;
use Illuminate\Support\Facades\DB;

/**
 * How many items finished production each day — counts transitions into
 * ready-for-delivery (the point production hands the item to dispatch) bucketed by
 * calendar day. Most recent day first. Narrow the window with date_from / date_to.
 */
final class ProductionDailyCompletionReport implements ReportInterface
{
    use FiltersByDateRange;

    public function kind(): string
    {
        return 'production_daily_completion';
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return ['Date', 'Completed Items'];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<list<string|int>>
     */
    public function rows(array $params, int $branchId): array
    {
        $query = DB::table('production_transitions')
            ->where('branch_id', $branchId)
            ->where('to_state', OrderItem::STATE_READY_FOR_DELIVERY)
            ->selectRaw('DATE(occurred_at) as day, COUNT(*) as total')
            ->groupByRaw('DATE(occurred_at)')
            ->orderByRaw('DATE(occurred_at) DESC');

        $this->applyDateRange($query, $params, 'occurred_at');

        return $query->get()
            ->map(fn ($row): array => [
                (string) $row->day,
                (int) $row->total,
            ])
            ->all();
    }
}
