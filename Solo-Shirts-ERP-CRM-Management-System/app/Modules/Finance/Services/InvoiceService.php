<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\Finance\Exceptions\FinanceException;
use App\Modules\Finance\Models\CreditNote;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderStatusDeriver;
use Illuminate\Support\Facades\DB;

/**
 * Builds GST invoices from an order and issues credit notes against them.
 * Invoices are append-only: corrections never edit an issued invoice, they add a
 * credit note. Numbering is gap-free per (branch, fiscal year).
 */
final class InvoiceService
{
    public function __construct(
        private readonly InvoiceNumberService $numbers,
        private readonly GstCalculator $gst,
        private readonly OrderStatusDeriver $statusDeriver,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Invoice
    {
        /** @var Order $order */
        $order = Order::query()->with('items')->findOrFail($data['order_id']);

        if ($this->statusDeriver->derive($order->items) === OrderStatusDeriver::CANCELLED) {
            throw FinanceException::orderCancelled();
        }

        $treatment = is_string($data['gst_treatment'] ?? null) ? $data['gst_treatment'] : Invoice::TREATMENT_REGULAR;
        $interState = (bool) ($data['inter_state'] ?? false);

        /** @var list<array<string, mixed>> $inputLines */
        $inputLines = $data['lines'] ?? [];

        $lines = array_map(static function (array $line): array {
            $quantity = (int) $line['quantity'];
            $unit = (int) $line['unit_price_paise'];

            return [
                'order_item_id' => $line['order_item_id'] ?? null,
                'description' => (string) $line['description'],
                'hsn_code' => $line['hsn_code'] ?? null,
                'quantity' => $quantity,
                'unit_price_paise' => $unit,
                'taxable_paise' => $quantity * $unit,
                'gst_rate' => (float) $line['gst_rate'],
            ];
        }, $inputLines);

        $taxInput = array_map(
            static fn (array $l): array => ['taxable_paise' => $l['taxable_paise'], 'gst_rate' => $l['gst_rate']],
            $lines,
        );

        $breakdown = $this->gst->calculate($taxInput, $treatment, $interState);

        $deliveryCharges = array_key_exists('delivery_charges_paise', $data) && $data['delivery_charges_paise'] !== null
            ? (int) $data['delivery_charges_paise']
            : $order->delivery_charges_paise;
        $discount = (int) ($data['discount_paise'] ?? 0);

        $total = $breakdown['subtotal_paise']
            + $breakdown['cgst_paise'] + $breakdown['sgst_paise'] + $breakdown['igst_paise']
            + $deliveryCharges - $discount;

        return DB::transaction(function () use (
            $order, $actor, $treatment, $breakdown, $lines, $deliveryCharges, $discount, $total
        ): Invoice {
            $fiscalYear = $this->numbers->fiscalYear();
            $order->loadMissing('branch');

            $invoice = Invoice::query()->create([
                'branch_id' => $order->branch_id,
                'invoice_no' => $this->numbers->nextInvoiceNumber($order->branch, $fiscalYear),
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'gst_treatment' => $treatment,
                'subtotal_paise' => $breakdown['subtotal_paise'],
                'cgst_paise' => $breakdown['cgst_paise'],
                'sgst_paise' => $breakdown['sgst_paise'],
                'igst_paise' => $breakdown['igst_paise'],
                'delivery_charges_paise' => $deliveryCharges,
                'discount_paise' => $discount,
                'total_paise' => $total,
                'issued_at' => now(),
                'issued_by' => $actor->id,
                'status' => Invoice::STATUS_ISSUED,
            ]);

            foreach ($lines as $index => $line) {
                $invoice->lines()->create([
                    'order_item_id' => $line['order_item_id'],
                    'description' => $line['description'],
                    'hsn_code' => $line['hsn_code'],
                    'quantity' => $line['quantity'],
                    'unit_price_paise' => $line['unit_price_paise'],
                    'taxable_paise' => $line['taxable_paise'],
                    'gst_rate' => $line['gst_rate'],
                    'tax_paise' => $breakdown['lines'][$index]['tax_paise'],
                ]);
            }

            return $invoice->load('lines');
        });
    }

    public function issueCreditNote(Invoice $invoice, string $reason, int $total, User $actor): CreditNote
    {
        $alreadyCredited = (int) $invoice->creditNotes()->sum('total_paise');

        if ($total <= 0 || $alreadyCredited + $total > $invoice->total_paise) {
            throw FinanceException::creditExceedsInvoice();
        }

        return DB::transaction(function () use ($invoice, $reason, $total, $actor): CreditNote {
            $fiscalYear = $this->numbers->fiscalYear();
            $invoice->loadMissing('branch');

            $creditNote = CreditNote::query()->create([
                'branch_id' => $invoice->branch_id,
                'credit_no' => $this->numbers->nextCreditNumber($invoice->branch, $fiscalYear),
                'invoice_id' => $invoice->id,
                'reason' => $reason,
                'total_paise' => $total,
                'issued_at' => now(),
                'issued_by' => $actor->id,
            ]);

            $this->reconcileStatus($invoice);

            return $creditNote;
        });
    }

    /**
     * Recompute an invoice's status from its payments and credit notes. Safe to
     * call after either is recorded; status is the only mutable money-adjacent
     * field the immutability trigger permits.
     */
    public function reconcileStatus(Invoice $invoice): void
    {
        $paid = (int) $invoice->payments()->sum('amount_paise');
        $credited = (int) $invoice->creditNotes()->sum('total_paise');

        $status = match (true) {
            $credited >= $invoice->total_paise && $invoice->total_paise > 0 => Invoice::STATUS_CREDITED,
            $paid + $credited >= $invoice->total_paise => Invoice::STATUS_PAID,
            $paid > 0 => Invoice::STATUS_PARTIALLY_PAID,
            default => Invoice::STATUS_ISSUED,
        };

        if ($status !== $invoice->status) {
            $invoice->update(['status' => $status]);
        }
    }
}
