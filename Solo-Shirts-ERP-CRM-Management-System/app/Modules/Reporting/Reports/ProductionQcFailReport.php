<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Reports;

use App\Modules\Production\Models\QcInspection;
use App\Modules\Reporting\Reports\Concerns\FiltersByDateRange;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * QC failures grouped by failure reason and the stage at which they were caught —
 * a Pareto-style view of why items fail inspection. Counts inspections whose
 * disposition sent the item back (rework) or rejected it; passes are ignored.
 * Complements DefectAnalyticsReport (which tallies defect categories instead).
 */
final class ProductionQcFailReport implements ReportInterface
{
    use FiltersByDateRange;

    public function kind(): string
    {
        return 'production_qc_fail';
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return ['Failure Reason', 'Failure Stage', 'Failures'];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<list<string|int>>
     */
    public function rows(array $params, int $branchId): array
    {
        $query = DB::table('qc_inspections')
            ->where('branch_id', $branchId)
            ->whereIn('disposition', [QcInspection::DISPOSITION_REWORK, QcInspection::DISPOSITION_REJECT])
            ->selectRaw('failure_reason, failure_stage, COUNT(*) as total')
            ->groupBy('failure_reason', 'failure_stage')
            ->orderByDesc('total');

        $this->applyDateRange($query, $params, 'inspected_at');

        return $query->get()
            ->map(fn ($row): array => [
                Str::headline((string) ($row->failure_reason ?? 'unspecified')),
                Str::headline((string) ($row->failure_stage ?? 'unknown')),
                (int) $row->total,
            ])
            ->all();
    }
}
