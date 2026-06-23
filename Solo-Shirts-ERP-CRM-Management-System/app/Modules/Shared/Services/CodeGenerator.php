<?php

declare(strict_types=1);

namespace App\Modules\Shared\Services;

use Illuminate\Support\Facades\DB;

/**
 * Generates gap-free, branch-prefixed sequential codes from a row-locked
 * counter table. The SELECT ... FOR UPDATE serializes concurrent callers so no
 * two requests ever receive the same number.
 */
final class CodeGenerator
{
    /**
     * @param  string  $sequenceTable  e.g. 'customer_sequences' with columns (branch_id, last_number)
     * @param  string  $prefix  e.g. 'SSI-HQ-'
     */
    public function next(string $sequenceTable, int $branchId, string $prefix, int $pad = 6): string
    {
        $number = DB::transaction(function () use ($sequenceTable, $branchId): int {
            $row = DB::table($sequenceTable)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                DB::table($sequenceTable)->insert([
                    'branch_id' => $branchId,
                    'last_number' => 1,
                ]);

                return 1;
            }

            $next = (int) $row->last_number + 1;

            DB::table($sequenceTable)
                ->where('branch_id', $branchId)
                ->update(['last_number' => $next]);

            return $next;
        });

        return $prefix . str_pad((string) $number, $pad, '0', STR_PAD_LEFT);
    }
}
