<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Services\BalanceService;
use App\Modules\Finance\Services\InvoiceService;
use App\Modules\Finance\Services\PaymentAllocationService;
use App\Modules\Finance\Services\PaymentService;
use App\Modules\Order\Exceptions\OrderException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates Front Desk order confirmation as ONE atomic transaction:
 *   1. promote lifecycle to order_received (validates box + PDF per sub-order)
 *   2. create the order's invoice if none exists (one line per sub-order)
 *   3. record the advance payment if one was provided
 *
 * Idempotent: re-confirming an already-received order creates nothing new and
 * returns the existing invoice/payment. Invoices use the `unregistered` GST
 * treatment (zero tax) for Phase 3A — real GST is a later phase.
 *
 * @phpstan-type ConfirmResult array{order: Order, invoice: ?Invoice, payment: ?Payment, balance_paise: int}
 */
final class OrderConfirmationService
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly InvoiceService $invoices,
        private readonly PaymentService $payments,
        private readonly BalanceService $balances,
        private readonly PaymentAllocationService $allocations,
    ) {}

    /**
     * @param  array<string, mixed>  $pricing  e.g. ['total_amount' => 4500]  (rupees)
     * @param  array<string, mixed>  $payment  e.g. ['advance_amount' => 2000, 'method' => 'upi', 'reference' => '…']
     * @return array{order: Order, invoice: Invoice|null, payment: Payment|null, balance_paise: int}
     */
    public function confirm(Order $order, User $actor, array $pricing, array $payment): array
    {
        return DB::transaction(function () use ($order, $actor, $pricing, $payment): array {
            // Only create finance records on the FIRST confirm — re-confirm is a no-op.
            $wasIntake = $order->lifecycle_status === Order::LIFECYCLE_INTAKE;

            // Validates box + PDF and promotes to order_received (idempotent).
            $order = $this->orders->confirmOrder($order, $actor);

            $invoice = Invoice::query()->where('order_id', $order->id)->first();

            $advancePaise = $this->paise($payment['advance_amount'] ?? null) ?? 0;
            $lines = $pricing['lines'] ?? null;

            if ($wasIntake && $invoice === null) {
                if (is_array($lines) && $lines !== []) {
                    // Phase 3C — per-shirt pricing (server computes every total).
                    $invoice = $this->invoices->create($this->invoiceDataFromLines($order, $lines), $actor);
                } else {
                    // Phase 3A fallback — a single manual total split across shirts.
                    $totalPaise = $this->paise($pricing['total_amount'] ?? null);
                    if ($totalPaise !== null) {
                        $invoice = $this->invoices->create([
                            'order_id' => $order->id,
                            'gst_treatment' => Invoice::TREATMENT_UNREGISTERED,
                            'inter_state' => false,
                            'discount_paise' => 0,
                            'delivery_charges_paise' => 0,
                            'lines' => $this->invoiceLines($order, $totalPaise),
                        ], $actor);
                    }
                }
            }

            // The advance can never exceed the (server-calculated) grand total.
            if ($wasIntake && $invoice !== null && $advancePaise > $invoice->total_paise) {
                throw OrderException::paymentExceedsBalance();
            }

            // Deterministic key → a retried confirm never double-records the advance.
            $idempotencyKey = 'order-confirm-advance:' . $order->id;
            $paymentModel = null;

            if ($wasIntake && $invoice !== null && $advancePaise > 0) {
                $paymentModel = $this->payments->record($invoice, [
                    'method' => $payment['method'],
                    'amount_paise' => $advancePaise,
                    'reference_no' => $payment['reference'] ?? null,
                ], $idempotencyKey, $actor);
            }

            $paymentModel ??= Payment::query()->where('idempotency_key', $idempotencyKey)->first();

            // Phase 1 — attribute the advance to each shirt proportionally so
            // item-level balances are accurate. Idempotent: a re-confirm that
            // returns the existing payment never re-allocates.
            if ($paymentModel !== null && $invoice !== null) {
                $this->allocations->allocateAdvanceAcrossInvoiceLines($paymentModel, $invoice, $actor);
            }

            $invoice?->refresh();
            $balancePaise = $invoice !== null ? $this->balances->outstandingForInvoice($invoice) : 0;

            return [
                'order' => $order->load('items', 'customer'),
                'invoice' => $invoice,
                'payment' => $paymentModel,
                'balance_paise' => $balancePaise,
            ];
        });
    }

    private function paise(mixed $rupees): ?int
    {
        if ($rupees === null || $rupees === '') {
            return null;
        }

        return (int) round(((float) $rupees) * 100);
    }

    /**
     * Phase 3C — build invoice lines from per-shirt pricing. The net taxable
     * (base + style + rush − discount) is computed server-side and passed as the
     * line unit price; GstCalculator then applies the rate. Treatment is
     * `regular` when any line is taxed, else `unregistered`. The breakdown is
     * persisted to the order item's design_notes (no dedicated invoice columns).
     *
     * @param  list<array<string, mixed>>  $lines
     * @return array<string, mixed>
     */
    private function invoiceDataFromLines(Order $order, array $lines): array
    {
        $items = $order->items()->get()
            ->reject(fn (OrderItem $i): bool => (string) $i->state === OrderItem::STATE_CANCELLED)
            ->keyBy('id');

        $invoiceLines = [];
        $covered = [];
        $anyTax = false;

        foreach ($lines as $line) {
            $itemId = (int) ($line['order_item_id'] ?? 0);
            /** @var OrderItem|null $item */
            $item = $items->get($itemId);

            if ($item === null || isset($covered[$itemId])) {
                throw OrderException::pricingLineMismatch();
            }
            $covered[$itemId] = true;

            $base = $this->paise($line['base_price'] ?? 0) ?? 0;
            $discount = $this->paise($line['discount_amount'] ?? 0) ?? 0;
            $rate = (float) ($line['gst_rate'] ?? 0);

            if ($discount > $base) {
                throw OrderException::discountExceedsLine($item->item_code);
            }

            $taxable = $base - $discount;
            if ($rate > 0) {
                $anyTax = true;
            }

            $design = is_array($item->design_notes) ? $item->design_notes : [];
            $bits = array_values(array_filter([$design['fabric'] ?? null, $design['style'] ?? null, $design['fit'] ?? null]));

            $invoiceLines[] = [
                'order_item_id' => $item->id,
                'description' => $item->item_code . ($bits !== [] ? ' — ' . implode(' / ', $bits) : ''),
                'quantity' => 1,
                'unit_price_paise' => $taxable,
                'gst_rate' => $rate,
            ];

            $design['pricing'] = [
                'base_price_paise' => $base,
                'discount_paise' => $discount,
                'gst_rate' => $rate,
                'taxable_paise' => $taxable,
            ];
            $item->forceFill(['design_notes' => $design])->save();
        }

        if (count($covered) !== $items->count()) {
            throw OrderException::pricingLinesIncomplete();
        }

        return [
            'order_id' => $order->id,
            'gst_treatment' => $anyTax ? Invoice::TREATMENT_REGULAR : Invoice::TREATMENT_UNREGISTERED,
            'inter_state' => false,
            'discount_paise' => 0,
            'delivery_charges_paise' => 0,
            'lines' => $invoiceLines,
        ];
    }

    /**
     * One invoice line per active sub-order. The manual order total is split
     * evenly across shirts (any remainder lands on the first lines) so the lines
     * sum back to exactly the total. Zero GST in Phase 3A.
     *
     * @return list<array<string, mixed>>
     */
    private function invoiceLines(Order $order, int $totalPaise): array
    {
        $items = $order->items()->get()
            ->reject(fn (OrderItem $item): bool => (string) $item->state === OrderItem::STATE_CANCELLED)
            ->values();

        $count = max(1, $items->count());
        $base = intdiv($totalPaise, $count);
        $remainder = $totalPaise % $count;

        $lines = [];

        foreach ($items as $i => $item) {
            $design = is_array($item->design_notes) ? $item->design_notes : [];
            $bits = array_values(array_filter([
                $design['fabric'] ?? null,
                $design['style'] ?? null,
                $design['fit'] ?? null,
            ]));

            $lines[] = [
                'order_item_id' => $item->id,
                'description' => $item->item_code . ($bits !== [] ? ' — ' . implode(' / ', $bits) : ''),
                'quantity' => 1,
                'unit_price_paise' => $base + ($i < $remainder ? 1 : 0),
                'gst_rate' => 0.0,
            ];
        }

        return $lines;
    }
}
