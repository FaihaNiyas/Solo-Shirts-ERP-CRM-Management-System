<?php

declare(strict_types=1);

namespace App\Modules\Finance\Console\Commands;

use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentAllocation;
use App\Modules\Finance\Services\PaymentAllocationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfills payment_allocations for payments recorded before Phase 1 existed.
 * Each unallocated payment is spread across its invoice's lines (oldest payment
 * first, so cumulative remaining balances stay correct), tagged `manual`.
 *
 *   php artisan solo:backfill-payment-allocations --dry-run
 *   php artisan solo:backfill-payment-allocations --confirm
 *
 * Idempotent (a payment that already has allocations is skipped), safe to re-run,
 * and reconciles sum(allocations) == sum(payments) at the end.
 */
final class BackfillPaymentAllocations extends Command
{
    protected $signature = 'solo:backfill-payment-allocations
        {--dry-run : Report what would be allocated; change nothing}
        {--confirm : Actually write the backfilled allocations}';

    protected $description = 'Backfill item-level payment_allocations for legacy invoice-level payments.';

    public function handle(PaymentAllocationService $allocations): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $confirm = (bool) $this->option('confirm');

        if ($dryRun === $confirm) {
            $this->error('Choose exactly one mode: --dry-run OR --confirm.');

            return self::FAILURE;
        }

        $env = app()->environment();
        if (! in_array($env, ['local', 'staging', 'testing'], true)) {
            $this->error("Refusing to run in '{$env}'. This backfill is for local/staging only.");

            return self::FAILURE;
        }

        // Oldest payment first so each allocation sees the correct remaining balance.
        $payments = Payment::query()->orderBy('id')->get();
        $planned = 0;
        $skipped = 0;

        foreach ($payments as $payment) {
            if (PaymentAllocation::query()->where('payment_id', $payment->id)->exists()) {
                $skipped++;

                continue;
            }

            $invoice = Invoice::query()->find($payment->invoice_id);
            if ($invoice === null) {
                $this->warn("Payment #{$payment->id} has no invoice — skipped.");

                continue;
            }

            $planned++;

            if ($confirm) {
                $allocations->allocatePaymentAcrossUnpaidLines(
                    $payment,
                    $invoice,
                    null,
                    PaymentAllocation::TYPE_MANUAL,
                );
            } else {
                $this->line("Would allocate payment #{$payment->id} ({$payment->amount_paise} paise) across invoice #{$invoice->id} lines.");
            }
        }

        $this->newLine();
        $this->info("Payments: {$payments->count()} | to allocate: {$planned} | already allocated (skipped): {$skipped}");

        if ($dryRun) {
            $this->info('DRY RUN — nothing was written. Re-run with --confirm to apply.');

            return self::SUCCESS;
        }

        // Reconcile: every paise of every payment must now be attributed.
        $paid = (int) Payment::query()->sum('amount_paise');
        $allocated = (int) PaymentAllocation::query()->sum('amount_paise');

        $this->table(['Metric', 'Paise'], [
            ['sum(payments.amount_paise)', number_format($paid)],
            ['sum(payment_allocations.amount_paise)', number_format($allocated)],
            ['difference', number_format($paid - $allocated)],
        ]);

        if ($paid !== $allocated) {
            $this->error('Reconciliation FAILED: allocations do not equal payments. Investigate before relying on item balances.');

            return self::FAILURE;
        }

        $this->info('Reconciliation OK: allocations equal payments.');

        return self::SUCCESS;
    }
}
