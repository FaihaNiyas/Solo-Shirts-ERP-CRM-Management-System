<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Modules\Production\Models\TailorAssignment;
use App\Modules\Shared\Services\BranchContext;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Computes tailor performance from completed assignments. Everything is done in
 * two aggregate queries (no N+1), branch-scoped to the caller's context, so the
 * endpoint stays fast even on large assignment histories. Phase 17 will add a
 * rollup table; this service is the on-demand source of truth meanwhile.
 */
final class TailorPerformanceService
{
    public function __construct(private readonly BranchContext $branch) {}

    /**
     * @return array{
     *     tailor_id: int,
     *     from: string,
     *     to: string,
     *     bundles_completed: int,
     *     pieces_completed: int,
     *     avg_minutes_per_piece: float,
     *     on_time_percentage: float,
     *     rework_count: int
     * }
     */
    public function performance(int $tailorId, Carbon $from, Carbon $to): array
    {
        $branchId = $this->branch->current();

        $row = DB::table('tailor_assignments as a')
            ->join('cut_bundles as b', 'b.id', '=', 'a.bundle_id')
            ->join('order_items as oi', 'oi.id', '=', 'a.order_item_id')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('a.tailor_id', $tailorId)
            ->where('a.status', TailorAssignment::STATUS_COMPLETED)
            ->whereBetween('a.completed_at', [$from, $to])
            ->when($branchId !== null, fn (Builder $q): Builder => $q->where('a.branch_id', $branchId))
            ->selectRaw('COUNT(*) as bundles_completed')
            ->selectRaw('COALESCE(SUM(b.pieces_count), 0) as pieces_completed')
            ->selectRaw('COALESCE(SUM(TIMESTAMPDIFF(MINUTE, a.started_at, a.completed_at)), 0) as total_minutes')
            ->selectRaw('SUM(CASE WHEN o.expected_delivery_date IS NULL OR DATE(a.completed_at) <= o.expected_delivery_date THEN 1 ELSE 0 END) as on_time')
            ->first();

        $bundles = (int) ($row->bundles_completed ?? 0);
        $pieces = (int) ($row->pieces_completed ?? 0);
        $minutes = (int) ($row->total_minutes ?? 0);
        $onTime = (int) ($row->on_time ?? 0);

        $reworkCount = (int) DB::table('production_transitions as pt')
            ->where('pt.to_state', 'rework')
            ->whereIn('pt.order_item_id', function (Builder $q) use ($tailorId, $from, $to, $branchId): void {
                $q->select('a2.order_item_id')
                    ->from('tailor_assignments as a2')
                    ->where('a2.tailor_id', $tailorId)
                    ->where('a2.status', TailorAssignment::STATUS_COMPLETED)
                    ->whereBetween('a2.completed_at', [$from, $to]);

                if ($branchId !== null) {
                    $q->where('a2.branch_id', $branchId);
                }
            })
            ->count();

        return [
            'tailor_id' => $tailorId,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'bundles_completed' => $bundles,
            'pieces_completed' => $pieces,
            'avg_minutes_per_piece' => $pieces > 0 ? round($minutes / $pieces, 2) : 0.0,
            'on_time_percentage' => $bundles > 0 ? round($onTime / $bundles * 100, 2) : 0.0,
            'rework_count' => $reworkCount,
        ];
    }
}
