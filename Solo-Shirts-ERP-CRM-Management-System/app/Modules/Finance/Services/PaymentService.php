<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\Finance\Exceptions\FinanceException;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Records payments against an invoice. Idempotent on the caller's key (the
 * payments table has a UNIQUE idempotency_key), so a retried request returns the
 * original payment instead of double-charging. After each payment the invoice
 * status is reconciled (issued → partially_paid → paid).
 */
final class PaymentService
{
    public function __construct(private readonly BalanceService $balances, private readonly InvoiceService $invoices) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(Invoice $invoice, array $data, string $idempotencyKey, User $actor): Payment
    {
        $existing = Payment::query()->where('idempotency_key', $idempotencyKey)->first();

        if ($existing !== null) {
            return $existing;
        }

        $amount = (int) $data['amount_paise'];

        // Advance payments (amount beyond the outstanding balance) are rejected
        // unless finance.allow_advance is enabled, in which case the surplus is
        // treated as an advance.
        $allowAdvance = (bool) config('finance.allow_advance', false);

        if (!$allowAdvance && $amount > $this->balances->outstandingForInvoice($invoice)) {
            throw FinanceException::paymentExceedsBalance();
        }

        return DB::transaction(function () use ($invoice, $data, $idempotencyKey, $actor, $amount): Payment {
            try {
                $payment = Payment::query()->create([
                    'branch_id' => $invoice->branch_id,
                    'invoice_id' => $invoice->id,
                    'method' => $data['method'],
                    'amount_paise' => $amount,
                    'reference_no' => $data['reference_no'] ?? null,
                    'paid_at' => $data['paid_at'] ?? now(),
                    'recorded_by' => $actor->id,
                    'upi_id' => $data['upi_id'] ?? null,
                    'bank_account_last4' => $data['bank_account_last4'] ?? null,
                    'idempotency_key' => $idempotencyKey,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Concurrent retry with the same key won the race.
                return Payment::query()->where('idempotency_key', $idempotencyKey)->firstOrFail();
            }

            $this->invoices->reconcileStatus($invoice);

            return $payment;
        });
    }
}
