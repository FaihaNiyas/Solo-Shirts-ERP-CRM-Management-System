<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Identity\Models\Branch;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Gap-free pickup-batch and receipt numbers per (branch, fiscal year), using the
 * same row-locked counter pattern as InvoiceNumberService so concurrent pickups
 * never share or skip a number. Indian fiscal year (Apr 1, IST).
 */
final class PickupNumberService
{
    public function nextBatchNumber(Branch $branch, int $fiscalYear): string
    {
        $number = $this->next('pickup_batch_sequences', $branch->id, $fiscalYear);

        return sprintf('SSI-%s-PB-%d-%05d', $branch->code, $fiscalYear, $number);
    }

    public function nextReceiptNumber(Branch $branch, int $fiscalYear): string
    {
        $number = $this->next('pickup_receipt_sequences', $branch->id, $fiscalYear);

        return sprintf('SSI-%s-RCPT-%d-%05d', $branch->code, $fiscalYear, $number);
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
                try {
                    DB::table($table)->insert([
                        'branch_id' => $branchId,
                        'fiscal_year' => $fiscalYear,
                        'last_number' => 0,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    // Created by a concurrent transaction; re-read under the lock.
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
