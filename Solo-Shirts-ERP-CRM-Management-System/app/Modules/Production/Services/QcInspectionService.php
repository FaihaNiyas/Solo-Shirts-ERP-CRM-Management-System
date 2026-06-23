<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Models\User;
use App\Modules\Order\Exceptions\OrderException;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Exceptions\QcException;
use App\Modules\Production\Models\ProductionTransition;
use App\Modules\Production\Models\QcDefect;
use App\Modules\Production\Models\QcDefectPhoto;
use App\Modules\Production\Models\QcInspection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Records a QC inspection and drives the resulting state transition through the
 * Phase 7 engine:
 *   pass / pass_with_note → Packing
 *   rework               → Rework (capped at 3, then needs override)
 *   reject               → Cancelled (flagged for refund — Phase 15)
 */
final class QcInspectionService
{
    public const MAX_REWORK = 3;

    public function __construct(private readonly StateTransitionService $transitions) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function inspect(int $itemId, array $payload, User $actor): QcInspection
    {
        $item = OrderItem::query()->with('order')->findOrFail($itemId);

        // An intake-preparation order is still at the Front Desk and is not yet a
        // production item — it can never be QC'd.
        if ($item->order?->isIntake()) {
            throw OrderException::notConfirmedForProduction();
        }

        if ((string) $item->state !== OrderItem::STATE_QC) {
            throw QcException::notInQc();
        }

        $disposition = is_string($payload['disposition'] ?? null) ? $payload['disposition'] : '';
        $notes = is_string($payload['notes'] ?? null) ? $payload['notes'] : null;
        $defects = is_array($payload['defects'] ?? null) ? $payload['defects'] : [];
        $failureReason = is_string($payload['failure_reason'] ?? null) ? $payload['failure_reason'] : null;
        $failureStage = is_string($payload['failure_stage'] ?? null) ? $payload['failure_stage'] : null;
        $reworkTarget = is_string($payload['rework_target_stage'] ?? null) ? $payload['rework_target_stage'] : null;

        if ($disposition === QcInspection::DISPOSITION_REWORK) {
            $this->guardReworkLimit($item, $actor);
        } else {
            // Failure detail only ever attaches to a rework disposition.
            $failureReason = $failureStage = $reworkTarget = null;
        }

        return DB::transaction(function () use ($item, $disposition, $notes, $defects, $failureReason, $failureStage, $reworkTarget, $actor): QcInspection {
            $previous = QcInspection::query()
                ->where('order_item_id', $item->id)
                ->orderByDesc('attempt_number')
                ->first();

            $inspection = QcInspection::query()->create([
                'order_item_id' => $item->id,
                'branch_id' => $item->branch_id,
                'attempt_number' => ($previous->attempt_number ?? 0) + 1,
                'previous_inspection_id' => $previous?->id,
                'disposition' => $disposition,
                'failure_reason' => $failureReason,
                'failure_stage' => $failureStage,
                'rework_target_stage' => $reworkTarget,
                'inspector_id' => $actor->id,
                'notes' => $notes,
                'inspected_at' => now(),
            ]);

            $this->persistDefects($inspection, $defects);

            $this->transitions->transition(
                $item->id,
                $this->targetState($disposition),
                $actor,
                (string) Str::uuid(),
                $notes,
                $disposition === QcInspection::DISPOSITION_REJECT ? ['refund' => true, 'reason' => 'qc_reject'] : [],
            );

            return $inspection->load('defects');
        });
    }

    private function guardReworkLimit(OrderItem $item, User $actor): void
    {
        $reworkVisits = ProductionTransition::query()
            ->where('order_item_id', $item->id)
            ->where('to_state', OrderItem::STATE_REWORK)
            ->count();

        if ($reworkVisits >= self::MAX_REWORK && !$actor->can('production.rework.override')) {
            throw QcException::reworkLimit();
        }
    }

    /**
     * @param  array<int|string, mixed>  $defects
     */
    private function persistDefects(QcInspection $inspection, array $defects): void
    {
        foreach ($defects as $defect) {
            if (!is_array($defect)) {
                continue;
            }

            $row = QcDefect::query()->create([
                'qc_inspection_id' => $inspection->id,
                'defect_category_id' => $defect['category_id'],
                'severity' => $defect['severity'],
                'notes' => $defect['notes'] ?? null,
            ]);

            $photoIds = is_array($defect['photo_ids'] ?? null) ? $defect['photo_ids'] : [];

            if ($photoIds !== []) {
                QcDefectPhoto::query()
                    ->whereIn('id', $photoIds)
                    ->whereNull('qc_defect_id')
                    ->update(['qc_defect_id' => $row->id]);
            }
        }
    }

    private function targetState(string $disposition): string
    {
        return match ($disposition) {
            QcInspection::DISPOSITION_REWORK => OrderItem::STATE_REWORK,
            QcInspection::DISPOSITION_REJECT => OrderItem::STATE_CANCELLED,
            default => OrderItem::STATE_PACKING,
        };
    }
}
