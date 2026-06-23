<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Identity\Models\Branch;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Issues gap-free, monotonic document numbers per (branch, fiscal year) from a
 * row-locked counter — never MAX()+1. The SELECT ... FOR UPDATE serializes
 * concurrent callers so two invoices can never share or skip a number. The
 * Indian fiscal year (Apr 1, IST) resets each sequence every April.
 */
final class InvoiceNumberService
{
    public function nextInvoiceNumber(Branch $branch, int $fiscalYear): string
    {
        $number = $this->next('invoice_sequences', $branch->id, $fiscalYear);

        return sprintf('SSI-%s-INV-%d-%05d', $branch->code, $fiscalYear, $number);
    }

    public function nextCreditNumber(Branch $branch, int $fiscalYear): string
    {
        $number = $this->next('credit_note_sequences', $branch->id, $fiscalYear);

        return sprintf('SSI-%s-CN-%d-%05d', $branch->code, $fiscalYear, $number);
    }

    public function fiscalYear(?Carbon $now = null): int
    {
        $ist = ($now ?? Carbon::now())->copy()->setTimezone('Asia/Kolkata');

        return $ist->month >= 4 ? $ist->year : $ist->year - 1;
    }

    private function next(string $table, int $branchId, int $fiscalYear): int
    {
        return DB::transaction(function () use ($table, $branchId, $fiscalYear): int {
            $row = DB::table($table)
                ->where('branch_id', $branchId)
                ->where('fiscal_year', $fiscalYear)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                // First number of the year. A concurrent initialiser may win the
                // race; swallow the duplicate and re-read under the lock.
                try {
                    DB::table($table)->insert([
                        'branch_id' => $branchId,
                        'fiscal_year' => $fiscalYear,
                        'last_number' => 0,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    // Already created by another transaction.
                }

                $row = DB::table($table)
                    ->where('branch_id', $branchId)
                    ->where('fiscal_year', $fiscalYear)
                    ->lockForUpdate()
                    ->first();
            }

            $next = (int) ($row->last_number ?? 0) + 1;

            DB::table($table)
                ->where('branch_id', $branchId)
                ->where('fiscal_year', $fiscalYear)
                ->update(['last_number' => $next]);

            return $next;
        });
    }
}
