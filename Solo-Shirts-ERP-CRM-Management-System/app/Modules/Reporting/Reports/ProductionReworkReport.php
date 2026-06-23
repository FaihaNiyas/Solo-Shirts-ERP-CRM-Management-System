<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Reports;

use App\Modules\Order\Models\OrderItem;
use App\Modules\Reporting\Reports\Concerns\FiltersByDateRange;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Items that have been routed back into rework, with how many times each one was
 * sent back. Counts `to_state = rework` transitions from the append-only ledger,
 * so an item that bounced twice shows a rework count of 2. Most-reworked first.
 */
final class ProductionReworkReport implements ReportInterface
{
    use FiltersByDateRange;

    public function kind(): string
    {
        return 'production_rework';
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return ['Item Code', 'Order Code', 'Current Stage', 'Rework Count'];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<list<string|int>>
     */
    public function rows(array $params, int $branchId): array
    {
        $query = DB::table('production_transitions as t')
            ->join('order_items as oi', 'oi.id', '=', 't.order_item_id')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('t.branch_id', $branchId)
            ->where('t.to_state', OrderItem::STATE_REWORK)
            ->whereNull('o.deleted_at')
            ->selectRaw('oi.item_code, o.order_code, oi.state, COUNT(*) as rework_count')
            ->groupBy('oi.id', 'oi.item_code', 'o.order_code', 'oi.state')
            ->orderByDesc('rework_count')
            ->limit(5000);

        $this->applyDateRange($query, $params, 't.occurred_at');

        return $query->get()
            ->map(fn ($row): array => [
                (string) $row->item_code,
                (string) $row->order_code,
                Str::headline((string) $row->state),
                (int) $row->rework_count,
            ])
            ->all();
    }
}
