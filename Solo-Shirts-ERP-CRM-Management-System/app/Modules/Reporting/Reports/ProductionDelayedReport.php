<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Reports;

use App\Modules\Order\Models\OrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Active items whose order is already past its expected delivery date — the items
 * running late on the floor. Delivered/cancelled items are never "delayed", and an
 * order with no expected date can't be overdue. Ordered most-overdue first.
 */
final class ProductionDelayedReport implements ReportInterface
{
    public function kind(): string
    {
        return 'production_delayed';
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return ['Item Code', 'Order Code', 'Stage', 'Priority', 'Expected Delivery', 'Days Overdue'];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<list<string|int>>
     */
    public function rows(array $params, int $branchId): array
    {
        $today = Carbon::today();

        return DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('oi.branch_id', $branchId)
            ->whereNull('o.deleted_at')
            ->whereNotIn('oi.state', [
                OrderItem::STATE_DRAFT,
                OrderItem::STATE_DELIVERED,
                OrderItem::STATE_CANCELLED,
            ])
            ->whereNotNull('o.expected_delivery_date')
            ->whereDate('o.expected_delivery_date', '<', $today->toDateString())
            ->orderBy('o.expected_delivery_date')
            ->limit(5000)
            ->get(['oi.item_code', 'o.order_code', 'oi.state', 'o.priority', 'o.expected_delivery_date'])
            ->map(fn ($row): array => [
                (string) $row->item_code,
                (string) $row->order_code,
                Str::headline((string) $row->state),
                Str::headline((string) ($row->priority ?? 'normal')),
                (string) Carbon::parse($row->expected_delivery_date)->toDateString(),
                (int) Carbon::parse($row->expected_delivery_date)->startOfDay()->diffInDays($today),
            ])
            ->all();
    }
}
