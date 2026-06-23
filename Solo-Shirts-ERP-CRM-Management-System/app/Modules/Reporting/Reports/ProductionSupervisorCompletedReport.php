<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Reports;

use App\Modules\Reporting\Reports\Concerns\FiltersByDateRange;
use Illuminate\Support\Facades\DB;

/**
 * How many stage hand-offs each person completed — the count of production
 * transitions they performed, keyed by the transition's actor. System/automated
 * transitions (no actor) are excluded. Busiest operator first. Bound an evaluation
 * window with the date_from / date_to params (applied to occurred_at).
 */
final class ProductionSupervisorCompletedReport implements ReportInterface
{
    use FiltersByDateRange;

    public function kind(): string
    {
        return 'production_supervisor_completed';
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return ['Supervisor', 'Stage Completions'];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<list<string|int>>
     */
    public function rows(array $params, int $branchId): array
    {
        $query = DB::table('production_transitions as t')
            ->leftJoin('users', 'users.id', '=', 't.actor_id')
            ->where('t.branch_id', $branchId)
            ->whereNotNull('t.actor_id')
            ->selectRaw('COALESCE(users.name, ?) as supervisor, COUNT(*) as total', ['Unknown'])
            ->groupBy('t.actor_id', 'users.name')
            ->orderByDesc('total');

        $this->applyDateRange($query, $params, 't.occurred_at');

        return $query->get()
            ->map(fn ($row): array => [
                (string) $row->supervisor,
                (int) $row->total,
            ])
            ->all();
    }
}
