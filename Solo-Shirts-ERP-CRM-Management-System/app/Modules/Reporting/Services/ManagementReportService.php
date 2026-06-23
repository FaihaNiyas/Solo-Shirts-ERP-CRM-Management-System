<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Services;

use App\Modules\Finance\Models\CreditNote;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\InvoiceLine;
use App\Modules\Finance\Models\Payment;
use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\PurchaseOrderItem;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Phase 9 — read-only management report aggregations. Every figure is read live
 * from the source-of-truth tables (orders, invoices, payments, order_items,
 * damage_reports, fabric_rolls/movements, purchase_orders). Money is in paise.
 * Nothing here mutates state. $branchId null means "all branches" (Owner only);
 * for staff the caller passes their own branch id so reports stay branch-scoped.
 */
final class ManagementReportService
{
    /** Production states shown in the pipeline (excludes draft/delivered/cancelled). */
    public const PRODUCTION_STAGES = [
        OrderItem::STATE_FABRIC_ALLOCATED,
        OrderItem::STATE_CUTTING,
        OrderItem::STATE_TAILORING,
        OrderItem::STATE_KAJA_BUTTON,
        OrderItem::STATE_FINISHING,
        OrderItem::STATE_QC,
        OrderItem::STATE_REWORK,
        OrderItem::STATE_PACKING,
        OrderItem::STATE_READY_FOR_DELIVERY,
    ];

    // ── Composite dashboard ────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?int $branchId, Carbon $from, Carbon $to): array
    {
        return [
            'date_range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'branch_id' => $branchId,
            'orders' => $this->ordersSummary($branchId, $from, $to),
            'payments' => $this->paymentsSummary($branchId, $from, $to),
            'production' => $this->productionCounts($branchId),
            'inventory' => $this->inventoryStock($branchId),
            'damage' => $this->damageSummary($branchId, $from, $to),
            'purchases' => $this->purchasesSummary($branchId, $from, $to),
        ];
    }

    // ── Orders ─────────────────────────────────────────────────────────────

    /**
     * @return array<string, int>
     */
    public function ordersSummary(?int $branchId, Carbon $from, Carbon $to): array
    {
        $orders = fn (): Builder => $this->branch(Order::query(), $branchId)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        return [
            'total_orders' => (clone $orders())->count(),
            'confirmed_orders' => (clone $orders())->where('lifecycle_status', Order::LIFECYCLE_ORDER_RECEIVED)->count(),
            'cancelled_orders' => (clone $orders())->where('lifecycle_status', Order::LIFECYCLE_CANCELLED)->count(),
            'delivered_orders' => (clone $orders())
                ->whereHas('items', fn (Builder $q) => $q->where('state', OrderItem::STATE_DELIVERED))
                ->whereDoesntHave('items', fn (Builder $q) => $q->whereNotIn('state', [OrderItem::STATE_DELIVERED, OrderItem::STATE_CANCELLED]))
                ->count(),
        ];
    }

    /**
     * Per-day order/item counts for the range.
     *
     * @return list<array<string, mixed>>
     */
    public function ordersDaily(?int $branchId, Carbon $from, Carbon $to, ?string $lifecycle = null): array
    {
        [$start, $end] = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];

        $orderRows = $this->branch(Order::query(), $branchId)
            ->when($lifecycle !== null, fn (Builder $q) => $q->where('lifecycle_status', $lifecycle))
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as orders, SUM(CASE WHEN lifecycle_status = ? THEN 1 ELSE 0 END) as cancelled', [Order::LIFECYCLE_CANCELLED])
            ->groupBy('d')->get()->keyBy('d');

        $itemRows = $this->branch(OrderItem::query(), $branchId)
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->selectRaw('DATE(orders.created_at) as d, COUNT(*) as items, SUM(CASE WHEN order_items.state = ? THEN 1 ELSE 0 END) as delivered', [OrderItem::STATE_DELIVERED])
            ->groupBy('d')->get()->keyBy('d');

        $rushRows = $this->branch(OrderItem::query(), $branchId)
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('order_items.design_notes->priority', 'rush')
            ->selectRaw('DATE(orders.created_at) as d, COUNT(*) as rush')
            ->groupBy('d')->get()->keyBy('d');

        $dates = $orderRows->keys()->merge($itemRows->keys())->unique()->sort()->values();

        return $dates->map(fn (string $d): array => [
            'date' => $d,
            'orders_count' => (int) ($orderRows[$d]->orders ?? 0),
            'cancelled_count' => (int) ($orderRows[$d]->cancelled ?? 0),
            'items_count' => (int) ($itemRows[$d]->items ?? 0),
            'delivered_count' => (int) ($itemRows[$d]->delivered ?? 0),
            'rush_count' => (int) ($rushRows[$d]->rush ?? 0),
        ])->all();
    }

    // ── Payments ───────────────────────────────────────────────────────────

    /**
     * @return array<string, int>
     */
    public function paymentsSummary(?int $branchId, Carbon $from, Carbon $to): array
    {
        [$start, $end] = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];

        $invoicedRange = (int) $this->branch(Invoice::query(), $branchId)->whereBetween('issued_at', [$start, $end])->sum('total_paise');
        $paidRange = (int) $this->branch(Payment::query(), $branchId)->whereBetween('paid_at', [$start, $end])->sum('amount_paise');

        // Money still owed right now (all open invoices, not range-bound).
        $invoicedAll = (int) $this->branch(Invoice::query(), $branchId)->sum('total_paise');
        $paidAll = (int) $this->branch(Payment::query(), $branchId)->sum('amount_paise');
        $creditedAll = (int) $this->branch(CreditNote::query(), $branchId)->sum('total_paise');

        return [
            'invoiced_paise' => $invoicedRange,
            'paid_paise' => $paidRange,
            'pending_paise' => $invoicedAll - $paidAll - $creditedAll,
        ];
    }

    /**
     * Open invoices with a positive balance, one row per invoice.
     *
     * @return list<array<string, mixed>>
     */
    public function pendingPayments(?int $branchId): array
    {
        $invoices = $this->branch(Invoice::query(), $branchId)
            ->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIALLY_PAID])
            ->with(['order:id,order_code,customer_id,expected_delivery_date', 'order.customer:id,name,phone_last4'])
            ->get();

        $ids = $invoices->pluck('id')->all();
        $paidById = Payment::query()->whereIn('invoice_id', $ids)->selectRaw('invoice_id, SUM(amount_paise) as p')->groupBy('invoice_id')->pluck('p', 'invoice_id');
        $creditById = CreditNote::query()->whereIn('invoice_id', $ids)->selectRaw('invoice_id, SUM(total_paise) as c')->groupBy('invoice_id')->pluck('c', 'invoice_id');

        return $invoices->map(function (Invoice $inv) use ($paidById, $creditById): array {
            $paid = (int) ($paidById[$inv->id] ?? 0);
            $credited = (int) ($creditById[$inv->id] ?? 0);
            $balance = $inv->total_paise - $paid - $credited;
            $last4 = $inv->order?->customer?->phone_last4;

            return [
                'invoice_no' => $inv->invoice_no,
                'order_code' => $inv->order?->order_code,
                'customer_name' => $inv->order?->customer?->name,
                'customer_phone' => $last4 ? '****' . $last4 : null,
                'invoice_total_paise' => (int) $inv->total_paise,
                'paid_paise' => $paid,
                'balance_paise' => $balance,
                'due_date' => $inv->order?->expected_delivery_date?->toDateString(),
                'days_pending' => $inv->issued_at !== null ? (int) $inv->issued_at->startOfDay()->diffInDays(now()->startOfDay()) : null,
            ];
        })
            ->filter(fn (array $row): bool => $row['balance_paise'] > 0)
            ->sortByDesc('balance_paise')
            ->values()->all();
    }

    // ── Production ─────────────────────────────────────────────────────────

    /**
     * @return array<string, int>
     */
    public function productionCounts(?int $branchId): array
    {
        $counts = $this->branch(OrderItem::query(), $branchId)
            ->selectRaw('state, COUNT(*) as c')->groupBy('state')->pluck('c', 'state');

        $out = [];
        foreach (self::PRODUCTION_STAGES as $stage) {
            $out[$stage] = (int) ($counts[$stage] ?? 0);
        }

        return $out;
    }

    /**
     * Per-stage counts with due-today / overdue / rush.
     *
     * @return list<array<string, mixed>>
     */
    public function productionStages(?int $branchId): array
    {
        $today = now()->toDateString();

        $counts = $this->branch(OrderItem::query(), $branchId)->selectRaw('state, COUNT(*) as c')->groupBy('state')->pluck('c', 'state');

        $dueToday = $this->branch(OrderItem::query(), $branchId)
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereDate('orders.expected_delivery_date', $today)
            ->selectRaw('order_items.state as state, COUNT(*) as c')->groupBy('state')->pluck('c', 'state');

        $overdue = $this->branch(OrderItem::query(), $branchId)
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereDate('orders.expected_delivery_date', '<', $today)
            ->selectRaw('order_items.state as state, COUNT(*) as c')->groupBy('state')->pluck('c', 'state');

        $rush = $this->branch(OrderItem::query(), $branchId)
            ->where('design_notes->priority', 'rush')
            ->selectRaw('state, COUNT(*) as c')->groupBy('state')->pluck('c', 'state');

        return collect(self::PRODUCTION_STAGES)->map(fn (string $stage): array => [
            'stage' => $stage,
            'count' => (int) ($counts[$stage] ?? 0),
            'due_today' => (int) ($dueToday[$stage] ?? 0),
            'overdue' => (int) ($overdue[$stage] ?? 0),
            'rush' => (int) ($rush[$stage] ?? 0),
        ])->all();
    }

    // ── Damage / waste ─────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function damageSummary(?int $branchId, Carbon $from, Carbon $to): array
    {
        $base = fn (): Builder => $this->branch(DamageReport::query(), $branchId)
            ->whereBetween('reported_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        return [
            'reported_count' => (clone $base())->count(),
            'reported_quantity' => $this->metres((float) (clone $base())->sum('quantity_lost_metres')),
            'approved_quantity' => $this->metres((float) (clone $base())->where('status', DamageReport::STATUS_APPROVED)->sum('quantity_lost_metres')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function damage(?int $branchId, Carbon $from, Carbon $to): array
    {
        $base = fn (): Builder => $this->branch(DamageReport::query(), $branchId)
            ->whereBetween('reported_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        $group = fn (string $col): array => (clone $base())
            ->selectRaw("$col as k, COUNT(*) as c, SUM(quantity_lost_metres) as q")->groupBy($col)->get()
            ->map(fn ($r): array => ['key' => $r->k, 'count' => (int) $r->c, 'quantity' => $this->metres((float) $r->q)])->all();

        $recent = (clone $base())->with(['order:id,order_code', 'orderItem:id,item_code'])->latest('id')->limit(20)->get()
            ->map(fn (DamageReport $d): array => [
                'id' => $d->id,
                'stage' => $d->stage,
                'damage_type' => $d->damage_type,
                'status' => $d->status,
                'quantity' => $this->metres((float) $d->quantity_lost_metres),
                'order_code' => $d->order?->order_code,
                'item_code' => $d->orderItem?->item_code,
                'reported_at' => $d->reported_at?->toDateString(),
            ])->all();

        return [
            'totals' => ['count' => (clone $base())->count(), 'quantity' => $this->metres((float) (clone $base())->sum('quantity_lost_metres'))],
            'by_status' => $group('status'),
            'by_stage' => $group('stage'),
            'by_type' => $group('damage_type'),
            'recent' => $recent,
        ];
    }

    // ── Sales / GST ────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function salesGst(?int $branchId, Carbon $from, Carbon $to): array
    {
        [$start, $end] = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];

        $inv = fn (): Builder => $this->branch(Invoice::query(), $branchId)->whereBetween('issued_at', [$start, $end]);

        $ids = (clone $inv())->pluck('id')->all();
        $paid = (int) Payment::query()->whereIn('invoice_id', $ids)->sum('amount_paise');
        $credited = (int) CreditNote::query()->whereIn('invoice_id', $ids)->sum('total_paise');
        $total = (int) (clone $inv())->sum('total_paise');

        $byRate = InvoiceLine::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_lines.invoice_id')
            ->when($branchId !== null, fn (Builder $q) => $q->where('invoices.branch_id', $branchId))
            ->whereBetween('invoices.issued_at', [$start, $end])
            ->selectRaw('invoice_lines.gst_rate as rate, SUM(invoice_lines.taxable_paise) as taxable, SUM(invoice_lines.tax_paise) as tax')
            ->groupBy('invoice_lines.gst_rate')->orderBy('rate')->get()
            ->map(fn ($r): array => ['gst_rate' => (float) $r->rate, 'taxable_paise' => (int) $r->taxable, 'tax_paise' => (int) $r->tax])->all();

        return [
            'invoice_count' => (clone $inv())->count(),
            'taxable_paise' => (int) (clone $inv())->sum('subtotal_paise'),
            'cgst_paise' => (int) (clone $inv())->sum('cgst_paise'),
            'sgst_paise' => (int) (clone $inv())->sum('sgst_paise'),
            'igst_paise' => (int) (clone $inv())->sum('igst_paise'),
            'total_paise' => $total,
            'paid_paise' => $paid,
            'balance_paise' => $total - $paid - $credited,
            'by_rate' => $byRate,
        ];
    }

    // ── Inventory stock ────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function inventoryStock(?int $branchId): array
    {
        $active = fn (): Builder => $this->branch(FabricRoll::query(), $branchId)->where('status', FabricRoll::STATUS_ACTIVE);

        $remaining = (float) (clone $active())->sum('remaining_metres');
        $lowStock = (clone $active())
            ->whereNotNull('low_stock_threshold_metres')
            ->whereColumn('remaining_metres', '<', 'low_stock_threshold_metres')
            ->count();

        $mv = fn (string $type): float => (float) $this->branch(FabricMovement::query(), $branchId)->where('type', $type)->sum('metres');
        $reserved = $mv(FabricMovement::TYPE_RESERVE) - $mv(FabricMovement::TYPE_RELEASE) - $mv(FabricMovement::TYPE_OUT);

        return [
            'fabric_rolls_count' => (clone $active())->count(),
            'low_stock_count' => $lowStock,
            'remaining_total' => $this->metres($remaining),
            'available_total' => $this->metres($remaining - $reserved),
            'reserved_total' => $this->metres(max(0.0, $reserved)),
            'consumed_total' => $this->metres($mv(FabricMovement::TYPE_OUT)),
            'damaged_total' => $this->metres($mv(FabricMovement::TYPE_DAMAGE_WRITEOFF)),
        ];
    }

    // ── Purchases / inward ─────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function purchasesSummary(?int $branchId, Carbon $from, Carbon $to): array
    {
        $base = fn (): Builder => $this->branch(PurchaseOrder::query(), $branchId)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        $byStatus = (clone $base())->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $receivedMetres = (float) PurchaseOrderItem::query()
            ->whereHas('purchaseOrder', fn (Builder $q) => $this->branch($q, $branchId)
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]))
            ->sum('received_metres');

        return [
            'purchase_orders_count' => (clone $base())->count(),
            'placed_count' => (int) ($byStatus[PurchaseOrder::STATUS_PLACED] ?? 0),
            'received_count' => (int) ($byStatus[PurchaseOrder::STATUS_RECEIVED] ?? 0) + (int) ($byStatus[PurchaseOrder::STATUS_PARTIAL_RECEIVED] ?? 0),
            'cancelled_count' => (int) ($byStatus[PurchaseOrder::STATUS_CANCELLED] ?? 0),
            'purchase_total_paise' => (int) (clone $base())->sum('total_paise'),
            'received_metres' => $this->metres($receivedMetres),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function purchases(?int $branchId, Carbon $from, Carbon $to): array
    {
        $summary = $this->purchasesSummary($branchId, $from, $to);

        $bySupplier = $this->branch(PurchaseOrder::query(), $branchId)
            ->whereBetween('purchase_orders.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->selectRaw('suppliers.name as supplier, COUNT(*) as orders, SUM(purchase_orders.total_paise) as total')
            ->groupBy('suppliers.name')->orderByDesc('total')->get()
            ->map(fn ($r): array => ['supplier' => $r->supplier, 'orders' => (int) $r->orders, 'total_paise' => (int) $r->total])->all();

        return [...$summary, 'by_supplier' => $bySupplier];
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /**
     * Apply an explicit branch filter. Scoped models also have the global
     * BranchScope (a no-op when null), but applying it here makes branch
     * isolation uniform across the unscoped ledgers too (fabric_movements).
     *
     * @template TQuery of Builder
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    private function branch(Builder $query, ?int $branchId): Builder
    {
        return $query->when($branchId !== null, fn (Builder $q) => $q->where($q->getModel()->getTable() . '.branch_id', $branchId));
    }

    private function metres(float $value): string
    {
        return number_format(round($value, 2), 2, '.', '');
    }
}
