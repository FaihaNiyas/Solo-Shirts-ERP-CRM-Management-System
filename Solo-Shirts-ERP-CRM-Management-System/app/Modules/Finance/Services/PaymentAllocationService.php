<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\InvoiceLine;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentAllocation;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Support\Collection;

/**
 * Item-level payment attribution (Phase 1). Splits each invoice-level payment
 * across the invoice's lines / order items so a per-item balance is possible,
 * WITHOUT touching the authoritative invoices/payments ledgers.
 *
 * All maths is in integer paise. Proportional splits round down per line, then
 * the leftover paise are handed out one at a time to the heaviest lines first
 * (ties broken by ascending invoice_line id) — deterministic and order-stable, so
 * the same inputs always produce the same allocation.
 *
 * Every payment is allocated in full, so sum(allocations) == sum(payments) and
 * therefore order balance == sum(item balances).
 */
final class PaymentAllocationService
{
    public function __construct(private readonly BalanceService $balances) {}

    /**
     * Spread a confirm-time advance proportionally across every invoice line by
     * the line's gross total. Idempotent: a payment that is already allocated is
     * left untouched (so a retried confirm never double-allocates).
     */
    public function allocateAdvanceAcrossInvoiceLines(Payment $payment, Invoice $invoice, ?User $actor): void
    {
        if ($this->paymentIsAllocated($payment)) {
            return;
        }

        $lines = $this->lines($invoice);
        $weights = $lines->mapWithKeys(fn (InvoiceLine $l): array => [$l->id => $this->lineTotal($l)])
            ->filter(fn (int $w): bool => $w > 0)->all();

        $this->writeLineAllocations($payment, $invoice, $lines, $weights, PaymentAllocation::TYPE_ADVANCE, $actor, null);
    }

    /**
     * Spread a full-order balance payment across the lines that still owe money,
     * proportionally to each line's remaining balance. Used by the existing
     * order-level payment endpoint.
     */
    public function allocatePaymentAcrossUnpaidLines(
        Payment $payment,
        Invoice $invoice,
        ?User $actor,
        string $type = PaymentAllocation::TYPE_FULL_ORDER_BALANCE,
    ): void {
        if ($this->paymentIsAllocated($payment)) {
            return;
        }

        $lines = $this->lines($invoice);
        $weights = $lines->mapWithKeys(fn (InvoiceLine $l): array => [$l->id => $this->lineRemaining($l)])
            ->filter(fn (int $w): bool => $w > 0)->all();

        $this->writeLineAllocations($payment, $invoice, $lines, $weights, $type, $actor, null);
    }

    /**
     * Spread a pickup-batch payment across ONLY the selected items, proportionally
     * to each selected item's remaining balance. Used by the pickup batch flow.
     *
     * @param  list<int>  $orderItemIds
     */
    public function allocatePaymentToSelectedItems(
        Payment $payment,
        Invoice $invoice,
        array $orderItemIds,
        ?User $actor,
        ?int $pickupBatchId,
        string $type = PaymentAllocation::TYPE_SELECTED_ITEM_BALANCE,
    ): void {
        if ($this->paymentIsAllocated($payment)) {
            return;
        }

        $selected = array_flip($orderItemIds);
        $lines = $this->lines($invoice)
            ->filter(fn (InvoiceLine $l): bool => $l->order_item_id !== null && isset($selected[$l->order_item_id]))
            ->values();

        $weights = $lines->mapWithKeys(fn (InvoiceLine $l): array => [$l->id => $this->lineRemaining($l)])
            ->filter(fn (int $w): bool => $w > 0)->all();

        $this->writeLineAllocations($payment, $invoice, $lines, $weights, $type, $actor, $pickupBatchId);
    }

    /**
     * Per-item payment picture used by the item payment-summary endpoint and the
     * pickup batch builder.
     *
     * @return array{
     *   item_id:int, item_code:string, invoice_id:?int, invoice_line_id:?int,
     *   item_total_paise:int, allocated_paid_paise:int, allocated_advance_paise:int,
     *   item_balance_paise:int
     * }
     */
    public function getItemPaymentSummary(OrderItem $item): array
    {
        $invoice = $this->invoiceForOrder($item->order_id);
        $line = $invoice === null
            ? null
            : InvoiceLine::query()->where('invoice_id', $invoice->id)->where('order_item_id', $item->id)->first();

        $itemTotal = $line !== null ? $this->lineTotal($line) : 0;
        $allocatedPaid = (int) PaymentAllocation::query()->where('order_item_id', $item->id)->sum('amount_paise');
        $allocatedAdvance = (int) PaymentAllocation::query()
            ->where('order_item_id', $item->id)
            ->where('allocation_type', PaymentAllocation::TYPE_ADVANCE)
            ->sum('amount_paise');

        return [
            'item_id' => $item->id,
            'item_code' => (string) $item->item_code,
            'invoice_id' => $invoice?->id,
            'invoice_line_id' => $line?->id,
            'item_total_paise' => $itemTotal,
            'allocated_paid_paise' => $allocatedPaid,
            'allocated_advance_paise' => $allocatedAdvance,
            'item_balance_paise' => max(0, $itemTotal - $allocatedPaid),
        ];
    }

    /**
     * Order-wide rollup: each item's balance plus the order total, and a
     * reconciliation flag asserting sum(item balances) == order balance.
     *
     * @return array{
     *   order_id:int, order_balance_paise:int, items_balance_paise:int,
     *   reconciled:bool, items: list<array<string, mixed>>
     * }
     */
    public function getOrderPaymentSummary(Order $order): array
    {
        $items = $order->relationLoaded('items') ? $order->items : $order->items()->get();

        $itemSummaries = $items
            ->reject(fn (OrderItem $i): bool => (string) $i->state === OrderItem::STATE_CANCELLED)
            ->map(fn (OrderItem $i): array => $this->getItemPaymentSummary($i))
            ->values()
            ->all();

        $itemsBalance = array_sum(array_column($itemSummaries, 'item_balance_paise'));
        $orderBalance = $this->balances->outstandingForOrder($order->id)['outstanding_paise'];

        return [
            'order_id' => $order->id,
            'order_balance_paise' => $orderBalance,
            'items_balance_paise' => $itemsBalance,
            'reconciled' => $itemsBalance === $orderBalance,
            'items' => $itemSummaries,
        ];
    }

    /** Remaining balance for one invoice line (gross total minus what's allocated). */
    public function lineRemaining(InvoiceLine $line): int
    {
        $allocated = (int) PaymentAllocation::query()->where('invoice_line_id', $line->id)->sum('amount_paise');

        return max(0, $this->lineTotal($line) - $allocated);
    }

    /** Gross total of an invoice line in paise (taxable + tax). */
    public function lineTotal(InvoiceLine $line): int
    {
        return (int) $line->taxable_paise + (int) $line->tax_paise;
    }

    /**
     * Core writer: given a weighted set of invoice lines, distribute the payment
     * amount across them and insert one allocation row per non-zero share.
     *
     * @param  Collection<int, InvoiceLine>  $lines
     * @param  array<int, int>  $weights  invoice_line_id => weight (>0)
     */
    private function writeLineAllocations(
        Payment $payment,
        Invoice $invoice,
        Collection $lines,
        array $weights,
        string $type,
        ?User $actor,
        ?int $pickupBatchId,
    ): void {
        $shares = $this->distribute((int) $payment->amount_paise, $weights);
        if ($shares === []) {
            return;
        }

        $lineById = $lines->keyBy('id');

        foreach ($shares as $lineId => $amount) {
            if ($amount <= 0) {
                continue;
            }

            /** @var InvoiceLine $line */
            $line = $lineById->get($lineId);

            PaymentAllocation::query()->create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'invoice_line_id' => $line->id,
                'order_id' => $invoice->order_id,
                'order_item_id' => $line->order_item_id,
                'pickup_batch_id' => $pickupBatchId,
                'amount_paise' => $amount,
                'allocation_type' => $type,
                'branch_id' => $invoice->branch_id,
                'created_by' => $actor?->id,
            ]);
        }
    }

    /**
     * Deterministic proportional split of $amount across weighted keys. Floor each
     * share, then hand the leftover paise out one at a time to the heaviest weights
     * first (ties broken by ascending key). Sums to exactly $amount.
     *
     * @param  array<int, int>  $weights  key => weight (>0)
     * @return array<int, int>  key => paise
     */
    private function distribute(int $amount, array $weights): array
    {
        $totalWeight = array_sum($weights);
        if ($amount <= 0 || $totalWeight <= 0) {
            return [];
        }

        $result = [];
        $allocated = 0;
        foreach ($weights as $key => $weight) {
            $share = intdiv($amount * $weight, $totalWeight);
            $result[$key] = $share;
            $allocated += $share;
        }

        $remainder = $amount - $allocated;
        if ($remainder > 0) {
            $order = array_keys($weights);
            usort($order, function (int $a, int $b) use ($weights): int {
                return $weights[$b] !== $weights[$a] ? $weights[$b] <=> $weights[$a] : $a <=> $b;
            });

            $count = count($order);
            for ($i = 0; $remainder > 0; $i++, $remainder--) {
                $result[$order[$i % $count]]++;
            }
        }

        return $result;
    }

    /**
     * @return Collection<int, InvoiceLine>
     */
    private function lines(Invoice $invoice): Collection
    {
        return $invoice->relationLoaded('lines')
            ? $invoice->lines
            : InvoiceLine::query()->where('invoice_id', $invoice->id)->orderBy('id')->get();
    }

    private function invoiceForOrder(int $orderId): ?Invoice
    {
        return Invoice::query()->where('order_id', $orderId)->latest('id')->first();
    }

    private function paymentIsAllocated(Payment $payment): bool
    {
        return PaymentAllocation::query()->where('payment_id', $payment->id)->exists();
    }
}
