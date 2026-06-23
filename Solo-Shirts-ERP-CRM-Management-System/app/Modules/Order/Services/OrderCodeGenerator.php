<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Identity\Models\Branch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Gap-free per-(branch, fiscal year) order codes from a row-locked counter.
 * Format: SSI-{branchCode}-ORD-000123. The Indian fiscal year starts April 1,
 * so the counter resets each April.
 */
final class OrderCodeGenerator
{
    public function next(Branch $branch): string
    {
        $fiscalYear = $this->fiscalYear();

        $number = DB::transaction(function () use ($branch, $fiscalYear): int {
            $row = DB::table('order_sequences')
                ->where('branch_id', $branch->id)
                ->where('fiscal_year', $fiscalYear)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                DB::table('order_sequences')->insert([
                    'branch_id' => $branch->id,
                    'fiscal_year' => $fiscalYear,
                    'last_number' => 1,
                ]);

                return 1;
            }

            $next = (int) $row->last_number + 1;

            DB::table('order_sequences')
                ->where('branch_id', $branch->id)
                ->where('fiscal_year', $fiscalYear)
                ->update(['last_number' => $next]);

            return $next;
        });

        return sprintf('SSI-%s-ORD-%06d', $branch->code, $number);
    }

    public function fiscalYear(?Carbon $now = null): int
    {
        $now ??= Carbon::now();

        return $now->month >= 4 ? $now->year : $now->year - 1;
    }
}
