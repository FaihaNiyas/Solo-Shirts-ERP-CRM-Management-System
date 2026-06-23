<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Models\User;
use App\Modules\Inventory\Contracts\StockLedgerInterface;
use App\Modules\Inventory\Exceptions\DamageException;
use App\Modules\Inventory\Exceptions\InventoryException;
use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Inventory\Models\DamageReportPhoto;
use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Inventory\Models\FabricRoll;
use Illuminate\Support\Facades\DB;

/**
 * Damage reporting and owner approval. Approval deducts stock ONLY through the
 * StockLedgerService (a damage_writeoff movement) inside the same transaction as
 * the status change, so the write-off and the approval commit or roll back
 * together. There is no other stock-deduction path.
 */
final class DamageReportService
{
    public function __construct(private readonly StockLedgerInterface $ledger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function report(array $data, User $actor): DamageReport
    {
        /** @var FabricRoll|null $roll */
        $roll = FabricRoll::query()->find($data['fabric_roll_id']);

        if ($roll === null) {
            throw DamageException::invalidRoll();
        }

        return DB::transaction(function () use ($data, $actor, $roll): DamageReport {
            $report = DamageReport::query()->create([
                'branch_id' => $roll->branch_id,
                'fabric_roll_id' => $roll->id,
                'order_id' => $data['order_id'] ?? null,
                'order_item_id' => $data['order_item_id'] ?? null,
                'reported_by' => $actor->id,
                'stage' => $data['stage'],
                'damage_type' => $data['damage_type'],
                'damage_type_other' => $data['damage_type_other'] ?? null,
                'quantity_lost_metres' => $data['quantity_lost_metres'],
                'action_taken' => $data['action_taken'] ?? null,
                'status' => DamageReport::STATUS_PENDING,
                'reported_at' => now(),
            ]);

            /** @var list<int> $photoIds */
            $photoIds = is_array($data['photo_ids'] ?? null) ? $data['photo_ids'] : [];

            if ($photoIds !== []) {
                DamageReportPhoto::query()
                    ->whereIn('id', $photoIds)
                    ->whereNull('damage_report_id')
                    ->update(['damage_report_id' => $report->id]);
            }

            return $report->load('photos');
        });
    }

    public function approve(DamageReport $report, ?string $notes, User $actor, string $idempotencyKey): DamageReport
    {
        $this->assertPending($report);

        return DB::transaction(function () use ($report, $notes, $actor, $idempotencyKey): DamageReport {
            $roll = FabricRoll::query()->lockForUpdate()->findOrFail($report->fabric_roll_id);

            if ($roll->status === FabricRoll::STATUS_WRITTEN_OFF) {
                throw InventoryException::rollWrittenOff();
            }

            // The ONLY stock-deduction path: a damage_writeoff ledger movement.
            // Throws INSUFFICIENT_STOCK if quantity_lost exceeds remaining, which
            // rolls the whole approval back.
            $this->ledger->record(
                $roll->id,
                FabricMovement::TYPE_DAMAGE_WRITEOFF,
                (float) $report->quantity_lost_metres,
                'damage write-off #' . $report->id,
                ['type' => 'damage_report', 'id' => $report->id],
                $actor->id,
                $idempotencyKey,
            );

            $report->update([
                'status' => DamageReport::STATUS_APPROVED,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'approval_notes' => $notes,
            ]);

            return $report->refresh();
        });
    }

    public function reject(DamageReport $report, string $reason, User $actor): DamageReport
    {
        $this->assertPending($report);

        $report->update([
            'status' => DamageReport::STATUS_REJECTED,
            'rejected_by' => $actor->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $report;
    }

    private function assertPending(DamageReport $report): void
    {
        if ($report->status === DamageReport::STATUS_APPROVED) {
            throw DamageException::alreadyApproved();
        }

        if ($report->status === DamageReport::STATUS_REJECTED) {
            throw DamageException::alreadyRejected();
        }
    }
}
