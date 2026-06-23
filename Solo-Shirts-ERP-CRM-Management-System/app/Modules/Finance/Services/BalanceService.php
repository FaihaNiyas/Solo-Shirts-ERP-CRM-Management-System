<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\CreditNote;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;

/**
 * Outstanding balance = sum(invoice totals) − sum(payments) − sum(credit notes).
 * Computed on demand from the append-only ledgers so it is always consistent
 * with what was actually recorded.
 */
final class BalanceService
{
    public function outstandingForInvoice(Invoice $invoice): int
    {
        $paid = (int) Payment::query()->where('invoice_id', $invoice->id)->sum('amount_paise');
        $credited = (int) CreditNote::query()->where('invoice_id', $invoice->id)->sum('total_paise');

        return $invoice->total_paise - $paid - $credited;
    }

    /**
     * @return array{invoiced_paise: int, paid_paise: int, credited_paise: int, outstanding_paise: int}
     */
    public function outstandingForOrder(int $orderId): array
    {
        $invoiceIds = Invoice::query()->where('order_id', $orderId)->pluck('id');

        return $this->summarise(
            (int) Invoice::query()->where('order_id', $orderId)->sum('total_paise'),
            $invoiceIds->all(),
        );
    }

    /**
     * @return array{invoiced_paise: int, paid_paise: int, credited_paise: int, outstanding_paise: int}
     */
    public function outstandingForCustomer(int $customerId): array
    {
        $invoiceIds = Invoice::query()->where('customer_id', $customerId)->pluck('id');

        return $this->summarise(
            (int) Invoice::query()->where('customer_id', $customerId)->sum('total_paise'),
            $invoiceIds->all(),
        );
    }

    /**
     * @param  list<int>  $invoiceIds
     * @return array{invoiced_paise: int, paid_paise: int, credited_paise: int, outstanding_paise: int}
     */
    private function summarise(int $invoiced, array $invoiceIds): array
    {
        $paid = (int) Payment::query()->whereIn('invoice_id', $invoiceIds)->sum('amount_paise');
        $credited = (int) CreditNote::query()->whereIn('invoice_id', $invoiceIds)->sum('total_paise');

        return [
            'invoiced_paise' => $invoiced,
            'paid_paise' => $paid,
            'credited_paise' => $credited,
            'outstanding_paise' => $invoiced - $paid - $credited,
        ];
    }
}
