<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Defect counts per category in a branch, joined through QC inspections so the
 * figures are branch-scoped.
 */
final class DefectAnalyticsReport implements ReportInterface
{
    public function kind(): string
    {
        return 'defect_analytics';
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return ['Defect Category ID', 'Count'];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<list<string|int>>
     */
    public function rows(array $params, int $branchId): array
    {
        return DB::table('qc_defects')
            ->join('qc_inspections', 'qc_defects.qc_inspection_id', '=', 'qc_inspections.id')
            ->where('qc_inspections.branch_id', $branchId)
            ->selectRaw('qc_defects.defect_category_id, COUNT(*) as total')
            ->groupBy('qc_defects.defect_category_id')
            ->get()
            ->map(fn ($row): array => [(int) $row->defect_category_id, (int) $row->total])
            ->all();
    }
}
