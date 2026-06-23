<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Services\BalanceService;
use App\Modules\Finance\Services\PaymentAllocationService;
use App\Modules\Finance\Services\PaymentService;
use App\Modules\Order\Exceptions\OrderException;
use App\Modules\Order\Http\Requests\RecordOrderPaymentRequest;
use App\Modules\Order\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Order-scoped balance collection for Front Desk. This is the ONLY payment path
 * exposed to Front Desk — it internally calls the shared PaymentService but is
 * bound to a single order's invoice, so the broad /finance/payments endpoint
 * stays closed. Payments are append-only and idempotent on the request key.
 */
final class OrderPaymentController extends BaseApiController
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly BalanceService $balances,
        private readonly PaymentAllocationService $allocations,
    ) {}

    /** Invoice balance summary + payment history for the order. */
    public function index(Order $order): JsonResponse
    {
        $this->authorize('view', Order::class);

        $invoice = $this->invoiceFor($order);

        return $this->respond([
            'order_id' => $order->id,
            'lifecycle_status' => $order->lifecycle_status,
            'invoice' => $invoice === null ? null : $this->invoiceSummary($invoice),
            'payments' => $invoice === null ? [] : $this->history($invoice),
        ]);
    }

    /** Record a balance payment against the order's invoice. */
    public function store(RecordOrderPaymentRequest $request, Order $order): JsonResponse
    {
        $this->authorize('collectPayment', Order::class);

        if ($order->lifecycle_status === Order::LIFECYCLE_INTAKE) {
            throw OrderException::notConfirmedForProduction();
        }
        if ($order->lifecycle_status === Order::LIFECYCLE_CANCELLED) {
            throw OrderException::cannotConfirmCancelled();
        }

        $invoice = $this->invoiceFor($order);
        if ($invoice === null) {
            throw OrderException::noInvoice();
        }

        $amountPaise = (int) round(((float) $request->input('amount')) * 100);

        // Clean, order-domain guard for over-payment / already-paid invoices,
        // independent of the finance allow_advance config. PaymentService is the
        // race-safe backstop.
        if ($amountPaise > $this->balances->outstandingForInvoice($invoice)) {
            throw OrderException::paymentExceedsBalance();
        }

        // Idempotent when the client supplies a key; otherwise each call is new.
        $key = $request->header('Idempotency-Key');
        $key = is_string($key) && trim($key) !== '' ? $key : (string) Str::uuid();

        /** @var User $actor */
        $actor = $request->user();

        $payment = $this->payments->record($invoice, [
            'method' => $request->input('method'),
            'amount_paise' => $amountPaise,
            'reference_no' => $request->input('reference'),
        ], $key, $actor);

        // Phase 1 — attribute this balance payment to the still-unpaid shirts so
        // per-item balances stay accurate. Idempotent on a replayed key.
        $this->allocations->allocatePaymentAcrossUnpaidLines($payment, $invoice, $actor);

        $invoice->refresh();

        return $this->respond([
            'invoice' => $this->invoiceSummary($invoice),
            'payment' => [
                'id' => $payment->id,
                'amount' => $payment->amount_paise / 100,
                'method' => $payment->method,
                'reference' => $payment->reference_no,
                'status' => 'recorded',
                // No dedicated payment-receipt PDF yet (Phase 3B-2).
                'receipt_url' => null,
            ],
        ], 'Payment recorded', 201);
    }

    private function invoiceFor(Order $order): ?Invoice
    {
        return Invoice::query()->where('order_id', $order->id)->latest('id')->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceSummary(Invoice $invoice): array
    {
        $balance = $this->balances->outstandingForInvoice($invoice);

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_no,
            'total_amount' => $invoice->total_paise / 100,
            'paid_amount' => ($invoice->total_paise - $balance) / 100,
            'balance_amount' => $balance / 100,
            'status' => $invoice->status,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function history(Invoice $invoice): array
    {
        return $invoice->payments()
            ->with('recorder:id,name')
            ->latest('paid_at')
            ->get()
            ->map(fn (Payment $p): array => [
                'id' => $p->id,
                'paid_at' => $p->paid_at?->toIso8601String(),
                'amount' => $p->amount_paise / 100,
                'method' => $p->method,
                'reference' => $p->reference_no,
                'recorded_by' => $p->recorder?->name,
            ])
            ->all();
    }
}
