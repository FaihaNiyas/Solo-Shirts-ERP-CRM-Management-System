<?php

declare(strict_types=1);

namespace App\Modules\Shared\Services;

use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Order\Models\Order;

/**
 * Cross-entity quick search for the global (Ctrl+K) palette. Results are limited
 * per entity and filtered by what the actor is allowed to see; branch isolation
 * is enforced automatically by the global BranchScope on each model. Returns a
 * normalized, navigable result list — never raw models.
 */
final class GlobalSearchService
{
    private const PER_ENTITY = 5;

    /**
     * @return list<array{id: int, type: string, title: string, subtitle: ?string, href: string}>
     */
    public function search(string $term, User $actor): array
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return [];
        }

        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
        $results = [];

        if ($actor->can('customers.view')) {
            Customer::query()
                ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('customer_code', 'like', $like))
                ->limit(self::PER_ENTITY)
                ->get()
                ->each(function (Customer $customer) use (&$results): void {
                    $results[] = [
                        'id' => $customer->id,
                        'type' => 'customer',
                        'title' => $customer->name,
                        'subtitle' => $customer->customer_code,
                        'href' => "/customers/{$customer->id}",
                    ];
                });
        }

        if ($actor->can('orders.view')) {
            Order::query()
                ->where('order_code', 'like', $like)
                ->with('customer:id,name')
                ->limit(self::PER_ENTITY)
                ->get()
                ->each(function (Order $order) use (&$results): void {
                    $results[] = [
                        'id' => $order->id,
                        'type' => 'order',
                        'title' => $order->order_code,
                        'subtitle' => $order->customer?->name,
                        'href' => "/orders/{$order->id}",
                    ];
                });
        }

        if ($actor->can('finance.view')) {
            Invoice::query()
                ->where('invoice_no', 'like', $like)
                ->with('customer:id,name')
                ->limit(self::PER_ENTITY)
                ->get()
                ->each(function (Invoice $invoice) use (&$results): void {
                    $results[] = [
                        'id' => $invoice->id,
                        'type' => 'invoice',
                        'title' => $invoice->invoice_no,
                        'subtitle' => $invoice->customer?->name,
                        'href' => "/finance/invoices/{$invoice->id}",
                    ];
                });
        }

        return $results;
    }
}
