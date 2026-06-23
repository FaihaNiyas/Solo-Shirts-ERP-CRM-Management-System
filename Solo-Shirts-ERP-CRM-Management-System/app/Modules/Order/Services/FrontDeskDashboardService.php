<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Finance\Models\CreditNote;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Order\Models\AlterationRequest;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\WhatsappNotification;
use Illuminate\Database\Eloquent\Builder;

/**
 * Aggregates branch-scoped, counter-operational metrics for the Front Desk
 * dashboard. Everything runs through the models' global BranchScope, so a
 * Front Desk user only ever sees their own branch's data. This is NOT a finance
 * report — it exposes only pending balance + today's collection, never revenue,
 * GST, margin or credit-note detail.
 */
final class FrontDeskDashboardService
{
    private const LIST_LIMIT = 10;

    private const ALTERATION_ACTIVE = [
        AlterationRequest::STATUS_INTAKE,
        AlterationRequest::STATUS_APPROVED,
        AlterationRequest::STATUS_IN_ALTERATION,
        AlterationRequest::STATUS_READY,
    ];

    /**
     * @return array<string, mixed>
     */
    public function summary(User $actor): array
    {
        $today = today();
        $showPhone = $this->canSeeFullPhone($actor);

        // Per-order outstanding balance (paise), branch-scoped. Reused throughout.
        $outstanding = $this->outstandingByOrder();

        // Orders that still have work / are not fully delivered.
        $openOrderIds = OrderItem::query()
            ->whereNotIn('state', [OrderItem::STATE_DELIVERED, OrderItem::STATE_CANCELLED])
            ->distinct()
            ->pluck('order_id')
            ->all();

        // Orders with at least one shirt staged ready for pickup.
        $readyOrderIds = OrderItem::query()
            ->where('state', OrderItem::STATE_READY_FOR_DELIVERY)
            ->distinct()
            ->pluck('order_id')
            ->all();

        $readyPending = 0;
        $readyPaid = 0;
        foreach ($readyOrderIds as $oid) {
            ($outstanding[$oid] ?? 0) > 0 ? $readyPending++ : $readyPaid++;
        }

        $pending = array_filter($outstanding, static fn (int $v): bool => $v > 0);

        $collectedTodayPaise = (int) Payment::query()->whereDate('paid_at', $today)->sum('amount_paise');

        return [
            'today' => [
                'new_orders_count' => Order::query()->whereDate('created_at', $today)->count(),
                'confirmed_orders_count' => Order::query()->whereDate('created_at', $today)
                    ->where('lifecycle_status', Order::LIFECYCLE_ORDER_RECEIVED)->count(),
                // Current intake orders not yet confirmed (answers "anything stuck in intake?").
                'intake_preparation_count' => Order::query()
                    ->where('lifecycle_status', Order::LIFECYCLE_INTAKE)->count(),
                'due_today_count' => $this->openOrdersDue($openOrderIds)->whereDate('expected_delivery_date', $today)->count(),
                'overdue_count' => $this->openOrdersDue($openOrderIds)->whereDate('expected_delivery_date', '<', $today)->count(),
            ],
            'pickup' => [
                'ready_for_pickup_count' => count($readyOrderIds),
                'ready_with_balance_pending_count' => $readyPending,
                'ready_fully_paid_count' => $readyPaid,
            ],
            'payments' => [
                'pending_balance_orders_count' => count($pending),
                'pending_balance_amount' => (int) round(array_sum($pending) / 100),
                'payments_collected_today' => (int) round($collectedTodayPaise / 100),
            ],
            'alterations' => [
                'active_count' => AlterationRequest::query()->whereIn('status', self::ALTERATION_ACTIVE)->count(),
                'ready_count' => AlterationRequest::query()->where('status', AlterationRequest::STATUS_READY)->count(),
                'intake_count' => AlterationRequest::query()->where('status', AlterationRequest::STATUS_INTAKE)->count(),
            ],
            'notifications' => [
                'whatsapp_failed_count' => WhatsappNotification::query()
                    ->where('status', WhatsappNotification::STATUS_FAILED)->whereDate('created_at', $today)->count(),
                'whatsapp_simulated_today_count' => WhatsappNotification::query()
                    ->where('status', WhatsappNotification::STATUS_SIMULATED)->whereDate('created_at', $today)->count(),
            ],
            'quick_lists' => [
                'due_today' => $this->dueTodayList($openOrderIds, $outstanding, $showPhone),
                'ready_for_pickup' => $this->readyForPickupList($readyOrderIds, $outstanding, $showPhone),
                'pending_balance' => $this->pendingBalanceList($pending, $outstanding, $showPhone),
                'active_alterations' => $this->activeAlterationsList($showPhone),
            ],
        ];
    }

    /**
     * Confirmed, not-fully-delivered orders — the set that can be "due" or "overdue".
     *
     * @param  list<int>  $openOrderIds
     * @return Builder<Order>
     */
    private function openOrdersDue(array $openOrderIds): Builder
    {
        return Order::query()
            ->whereIn('id', $openOrderIds)
            ->where('lifecycle_status', Order::LIFECYCLE_ORDER_RECEIVED)
            ->whereNotNull('expected_delivery_date');
    }

    /**
     * Outstanding balance per order in paise (invoiced − paid − credited),
     * branch-scoped. Only orders that carry an invoice appear.
     *
     * @return array<int, int>
     */
    private function outstandingByOrder(): array
    {
        $invoiced = Invoice::query()
            ->selectRaw('order_id, SUM(total_paise) as t')
            ->groupBy('order_id')
            ->pluck('t', 'order_id');

        $paid = Payment::query()
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->selectRaw('invoices.order_id as oid, SUM(payments.amount_paise) as p')
            ->groupBy('invoices.order_id')
            ->pluck('p', 'oid');

        $credited = CreditNote::query()
            ->join('invoices', 'invoices.id', '=', 'credit_notes.invoice_id')
            ->selectRaw('invoices.order_id as oid, SUM(credit_notes.total_paise) as c')
            ->groupBy('invoices.order_id')
            ->pluck('c', 'oid');

        $out = [];
        foreach ($invoiced as $orderId => $total) {
            $out[(int) $orderId] = (int) $total - (int) ($paid[$orderId] ?? 0) - (int) ($credited[$orderId] ?? 0);
        }

        return $out;
    }

    /**
     * @param  list<int>  $openOrderIds
     * @param  array<int, int>  $outstanding
     * @return list<array<string, mixed>>
     */
    private function dueTodayList(array $openOrderIds, array $outstanding, bool $showPhone): array
    {
        $orders = $this->openOrdersDue($openOrderIds)
            ->whereDate('expected_delivery_date', today())
            ->with('customer:id,name,phone,phone_last4')
            ->orderBy('expected_delivery_date')
            ->limit(self::LIST_LIMIT)
            ->get();

        return $orders->map(fn (Order $o): array => [
            'order_id' => $o->id,
            'order_code' => $o->order_code,
            'customer_name' => $o->customer?->name,
            ...$this->phoneFields($o->customer, $showPhone),
            'delivery_date' => $o->expected_delivery_date?->toDateString(),
            'status' => $o->lifecycle_status,
            'balance_amount' => (int) round(($outstanding[$o->id] ?? 0) / 100),
        ])->all();
    }

    /**
     * @param  list<int>  $readyOrderIds
     * @param  array<int, int>  $outstanding
     * @return list<array<string, mixed>>
     */
    private function readyForPickupList(array $readyOrderIds, array $outstanding, bool $showPhone): array
    {
        $orders = Order::query()
            ->whereIn('id', $readyOrderIds)
            ->with(['customer:id,name,phone,phone_last4', 'items:id,order_id,state'])
            ->limit(self::LIST_LIMIT)
            ->get();

        $readyItemIds = $orders->flatMap(
            fn (Order $o) => $o->items->where('state', OrderItem::STATE_READY_FOR_DELIVERY)->pluck('id')
        )->all();

        $slotByItem = RackSlot::query()
            ->whereIn('current_order_item_id', $readyItemIds)
            ->pluck('slot_code', 'current_order_item_id');

        return $orders->map(function (Order $o) use ($outstanding, $slotByItem, $showPhone): array {
            $slots = $o->items
                ->where('state', OrderItem::STATE_READY_FOR_DELIVERY)
                ->map(fn (OrderItem $i) => $slotByItem[$i->id] ?? null)
                ->filter()
                ->values()
                ->all();
            $balance = (int) ($outstanding[$o->id] ?? 0);

            return [
                'order_id' => $o->id,
                'order_code' => $o->order_code,
                'customer_name' => $o->customer?->name,
                ...$this->phoneFields($o->customer, $showPhone),
                'rack_slots' => $slots,
                'balance_amount' => (int) round($balance / 100),
                'payment_status' => $balance > 0 ? 'balance_pending' : 'paid',
            ];
        })->all();
    }

    /**
     * @param  array<int, int>  $pending
     * @param  array<int, int>  $outstanding
     * @return list<array<string, mixed>>
     */
    private function pendingBalanceList(array $pending, array $outstanding, bool $showPhone): array
    {
        arsort($pending); // biggest balances first
        $orderIds = array_slice(array_keys($pending), 0, self::LIST_LIMIT);

        if ($orderIds === []) {
            return [];
        }

        $orders = Order::query()
            ->whereIn('id', $orderIds)
            ->with('customer:id,name,phone,phone_last4')
            ->get()
            ->keyBy('id');

        $lastPayment = Payment::query()
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->whereIn('invoices.order_id', $orderIds)
            ->selectRaw('invoices.order_id as oid, MAX(payments.paid_at) as last_paid')
            ->groupBy('invoices.order_id')
            ->pluck('last_paid', 'oid');

        $rows = [];
        foreach ($orderIds as $oid) {
            $o = $orders->get($oid);
            if ($o === null) {
                continue;
            }
            $last = $lastPayment[$oid] ?? null;
            $rows[] = [
                'order_id' => $o->id,
                'order_code' => $o->order_code,
                'customer_name' => $o->customer?->name,
                ...$this->phoneFields($o->customer, $showPhone),
                'balance_amount' => (int) round(($outstanding[$oid] ?? 0) / 100),
                'last_payment_date' => $last !== null ? substr((string) $last, 0, 10) : null,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function activeAlterationsList(bool $showPhone): array
    {
        $alterations = AlterationRequest::query()
            ->whereIn('status', self::ALTERATION_ACTIVE)
            ->with(['customer:id,name,phone,phone_last4', 'order:id,order_code', 'orderItem:id,item_code'])
            ->latest('id')
            ->limit(self::LIST_LIMIT)
            ->get();

        return $alterations->map(fn (AlterationRequest $a): array => [
            'alteration_id' => $a->id,
            'customer_name' => $a->customer?->name,
            ...$this->phoneFields($a->customer, $showPhone),
            'order_code' => $a->order?->order_code,
            'item_code' => $a->orderItem?->item_code,
            'status' => $a->status,
            'priority' => $a->priority,
        ])->all();
    }

    /**
     * @return array{phone: string|null, phone_masked: string|null}
     */
    private function phoneFields(?Customer $customer, bool $showPhone): array
    {
        $last4 = $customer?->phone_last4;

        return [
            'phone' => $showPhone ? $customer?->phone : null,
            'phone_masked' => $last4 !== null && $last4 !== '' ? '****' . $last4 : null,
        ];
    }

    private function canSeeFullPhone(User $user): bool
    {
        return $user->hasAnyRole(['Owner', 'Admin', 'Front Desk']);
    }
}
