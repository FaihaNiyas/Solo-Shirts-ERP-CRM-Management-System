<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Reports;

use App\Modules\Finance\Models\Invoice;

/**
 * Invoices issued in a branch with their running status.
 */
final class FinanceSummaryReport implements ReportInterface
{
    public function kind(): string
    {
        return 'finance_summary';
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return ['Invoice No', 'Total (paise)', 'Status', 'Issued At'];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<list<string|int>>
     */
    public function rows(array $params, int $branchId): array
    {
        return Invoice::query()->withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->latest('id')
            ->limit(5000)
            ->get()
            ->map(fn (Invoice $invoice): array => [
                $invoice->invoice_no,
                $invoice->total_paise,
                $invoice->status,
                $invoice->issued_at->toDateTimeString(),
            ])
            ->all();
    }
}
