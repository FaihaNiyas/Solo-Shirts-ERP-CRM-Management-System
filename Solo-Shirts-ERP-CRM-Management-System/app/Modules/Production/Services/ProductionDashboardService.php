<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Models\ProductionTransition;
use App\Modules\Shared\Services\BranchContext;
use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * Live production dashboard metrics (Kanban Phase D). Unlike the Phase 17 reporting
 * dashboard (rollup-only), this is an operational view computed straight from the
 * OLTP tables so it always agrees with the live board. Branch isolation: the
 * OrderItem queries inherit the global scope; the transition queries (which have no
 * global scope) are filtered by the active branch_id explicitly.
 */
final class ProductionDashboardService
{
    /** The shop-floor stages that count as "in production". */
    private const ACTIVE = [
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

    public function __construct(private readonly BranchContext $branchContext) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $byStage = $this->countsByStage();
        $avgHours = $this->avgHoursInStage();

        return [
            'total_active' => array_sum($byStage),
            'by_stage' => $byStage,
            'delayed' => $this->delayedCount(),
            'urgent' => $this->elevatedPriorityCount(),
            'on_hold' => $this->onHoldCount(),
            'in_rework' => $byStage[OrderItem::STATE_REWORK] ?? 0,
            'pending_qc' => $byStage[OrderItem::STATE_QC] ?? 0,
            'ready_for_delivery' => $byStage[OrderItem::STATE_READY_FOR_DELIVERY] ?? 0,
            'completed_today' => $this->completedTodayCount(),
            'avg_hours_in_stage' => $avgHours,
            'bottleneck_stage' => $this->bottleneck($avgHours),
        ];
    }

    /**
     * Live count of items in each active stage (zero-filled for every stage).
     *
     * @return array<string, int>
     */
    private function countsByStage(): array
    {
        $counts = array_fill_keys(self::ACTIVE, 0);

        $rows = $this->activeItems()
            ->selectRaw('state, COUNT(*) as aggregate')
            ->groupBy('state')
            ->pluck('aggregate', 'state');

        foreach ($rows as $state => $count) {
            $counts[(string) $state] = (int) $count;
        }

        return $counts;
    }

    private function delayedCount(): int
    {
        return $this->activeItems()
            ->whereHas('order', fn (Builder $q): Builder => $q->whereDate('expected_delivery_date', '<', now()->toDateString()))
            ->count();
    }

    /** Elevated priority = high or urgent (the two non-default levels). */
    private function elevatedPriorityCount(): int
    {
        return $this->activeItems()
            ->whereHas('order', fn (Builder $q): Builder => $q->whereIn('priority', [Order::PRIORITY_HIGH, Order::PRIORITY_URGENT]))
            ->count();
    }

    private function onHoldCount(): int
    {
        return $this->activeItems()->whereNotNull('on_hold_at')->count();
    }

    /** Items whose production finished today (a transition into ready_for_delivery). */
    private function completedTodayCount(): int
    {
        return $this->branchTransitions()
            ->where('to_state', OrderItem::STATE_READY_FOR_DELIVERY)
            ->whereDate('occurred_at', now()->toDateString())
            ->distinct()
            ->count('order_item_id');
    }

    /**
     * Average completed hours spent in each stage, derived from consecutive
     * transitions: time in a state = the gap between entering it and leaving it.
     * The current (in-progress) state of each item is not counted.
     *
     * @return array<string, float>
     */
    private function avgHoursInStage(): array
    {
        $rows = $this->branchTransitions()
            ->whereNotNull('from_state')
            ->orderBy('order_item_id')
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get(['order_item_id', 'from_state', 'occurred_at']);

        /** @var array<string, array{sum: float, count: int}> $acc */
        $acc = [];
        $prevItem = null;
        $prevAt = null;

        foreach ($rows as $row) {
            if ($prevItem === $row->order_item_id && $prevAt !== null) {
                // The gap is the time the item spent in this transition's from_state
                // (which is the state it occupied since the previous transition).
                $stage = (string) $row->from_state;
                $seconds = $row->occurred_at->getTimestamp() - $prevAt;
                if ($seconds >= 0) {
                    $acc[$stage]['sum'] = ($acc[$stage]['sum'] ?? 0) + $seconds;
                    $acc[$stage]['count'] = ($acc[$stage]['count'] ?? 0) + 1;
                }
            }

            $prevItem = $row->order_item_id;
            $prevAt = $row->occurred_at->getTimestamp();
        }

        $avg = [];
        foreach ($acc as $stage => $data) {
            if ($data['count'] > 0) {
                $avg[$stage] = round($data['sum'] / $data['count'] / 3600, 1);
            }
        }

        return $avg;
    }

    /**
     * The slowest stage by average dwell time — the production bottleneck.
     *
     * @param  array<string, float>  $avgHours
     * @return array{stage: string, avg_hours: float}|null
     */
    private function bottleneck(array $avgHours): ?array
    {
        if ($avgHours === []) {
            return null;
        }

        $stage = array_keys($avgHours, max($avgHours), true)[0];

        return ['stage' => $stage, 'avg_hours' => $avgHours[$stage]];
    }

    /**
     * Active, non-intake production items in the current branch (global scope).
     *
     * @return Builder<OrderItem>
     */
    private function activeItems(): Builder
    {
        return OrderItem::query()
            ->whereIn('state', self::ACTIVE)
            ->whereHas('order', fn (Builder $q): Builder => $q->where('lifecycle_status', '!=', Order::LIFECYCLE_INTAKE));
    }

    /**
     * Production transitions for the active branch (the table has no global scope).
     *
     * @return Builder<ProductionTransition>
     */
    private function branchTransitions(): Builder
    {
        $query = ProductionTransition::query();
        $branchId = $this->branchContext->current();

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }
}
