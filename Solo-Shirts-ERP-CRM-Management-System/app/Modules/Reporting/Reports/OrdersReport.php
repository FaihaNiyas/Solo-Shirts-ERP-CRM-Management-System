<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Reports;

use App\Modules\Order\Models\Order;

/**
 * Orders placed in a branch, newest first.
 */
final class OrdersReport implements ReportInterface
{
    public function kind(): string
    {
        return 'orders';
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return ['Order Code', 'Customer ID', 'Items', 'Created At'];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<list<string|int>>
     */
    public function rows(array $params, int $branchId): array
    {
        return Order::query()->withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->withCount('items')
            ->latest('id')
            ->limit(5000)
            ->get()
            ->map(fn (Order $order): array => [
                $order->order_code,
                $order->customer_id,
                (int) ($order->items_count ?? 0),
                (string) $order->created_at?->toDateTimeString(),
            ])
            ->all();
    }
}
