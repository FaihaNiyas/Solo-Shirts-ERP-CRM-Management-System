<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Completed assignment counts per tailor in a branch.
 */
final class TailorPerformanceReport implements ReportInterface
{
    public function kind(): string
    {
        return 'tailor_performance';
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return ['Tailor ID', 'Completed Assignments'];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<list<string|int>>
     */
    public function rows(array $params, int $branchId): array
    {
        return DB::table('tailor_assignments')
            ->where('branch_id', $branchId)
            ->whereNotNull('completed_at')
            ->selectRaw('tailor_id, COUNT(*) as completed')
            ->groupBy('tailor_id')
            ->get()
            ->map(fn ($row): array => [(int) $row->tailor_id, (int) $row->completed])
            ->all();
    }
}
