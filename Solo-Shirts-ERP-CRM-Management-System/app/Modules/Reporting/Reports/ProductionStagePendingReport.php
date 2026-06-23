<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Reports;

use App\Modules\Order\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * How many items are currently sitting in each production stage (the live WIP per
 * column of the kanban board). Terminal states (draft / delivered / cancelled) are
 * excluded — only work-in-progress counts. Every workshop stage is emitted in
 * workflow order, including those at zero, so the report shape is stable.
 */
final class ProductionStagePendingReport implements ReportInterface
{
    /**
     * The stages that represent live shop-floor WIP, in workflow order.
     *
     * @var list<string>
     */
    private const STAGES = [
        OrderItem::STATE_FABRIC_ALLOCATED,
        OrderItem::STATE_CUTTING,
        OrderItem::STATE_TAILORING,
        OrderItem::STATE_KAJA_BUTTON,
        OrderItem::STATE_FINISHING,
        OrderItem::STATE_QC,
        OrderItem::STATE_REWORK,
        OrderItem::STATE_PACKING,
        OrderItem::STATE_READY_FOR_DELIVERY,
    ];

    public function kind(): string
    {
        return 'production_stage_pending';
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return ['Stage', 'Pending Items'];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<list<string|int>>
     */
    public function rows(array $params, int $branchId): array
    {
        /** @var array<string, int> $counts */
        $counts = DB::table('order_items')
            ->where('branch_id', $branchId)
            ->whereIn('state', self::STAGES)
            ->selectRaw('state, COUNT(*) as total')
            ->groupBy('state')
            ->pluck('total', 'state')
            ->map(fn ($total): int => (int) $total)
            ->all();

        return array_map(
            fn (string $stage): array => [Str::headline($stage), $counts[$stage] ?? 0],
            self::STAGES,
        );
    }
}
