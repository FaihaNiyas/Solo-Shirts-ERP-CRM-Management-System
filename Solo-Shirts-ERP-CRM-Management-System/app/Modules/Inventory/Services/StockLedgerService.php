<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Contracts\StockLedgerInterface;
use App\Modules\Inventory\Exceptions\InventoryException;
use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Inventory\Models\FabricRoll;
use Illuminate\Support\Facades\DB;

/**
 * The append-only fabric stock ledger. Every movement is one row; the roll's
 * cached remaining_metres is updated inside the same locked transaction, so the
 * cache and the ledger never diverge under concurrency. `metres` is stored as a
 * positive magnitude — `type` decides whether it adds, deducts, or is a soft hold.
 *
 *   remaining = Σ(receive, adjust_in) − Σ(out, adjust_out, damage_writeoff)
 *   available = remaining − (Σreserve − Σrelease − Σout)
 */
final class StockLedgerService implements StockLedgerInterface
{
    public function availableMetres(FabricRoll $roll): float
    {
        $reserved = $this->sum($roll->id, FabricMovement::TYPE_RESERVE);
        $released = $this->sum($roll->id, FabricMovement::TYPE_RELEASE);
        $out = $this->sum($roll->id, FabricMovement::TYPE_OUT);

        $activeReserved = $reserved - $released - $out;

        return (float) $roll->remaining_metres - $activeReserved;
    }

    /**
     * The roll's full stock position in one pass.
     *
     * @return array{remaining: float, available: float, reserved: float, consumed: float, damaged: float}
     */
    public function breakdown(FabricRoll $roll): array
    {
        $reserved = $this->sum($roll->id, FabricMovement::TYPE_RESERVE);
        $released = $this->sum($roll->id, FabricMovement::TYPE_RELEASE);
        $out = $this->sum($roll->id, FabricMovement::TYPE_OUT);
        $damaged = $this->sum($roll->id, FabricMovement::TYPE_DAMAGE_WRITEOFF);

        $activeReserved = $reserved - $released - $out;
        $remaining = (float) $roll->remaining_metres;

        return [
            'remaining' => $remaining,
            'available' => $remaining - $activeReserved,
            'reserved' => max(0.0, $activeReserved),
            'consumed' => $out,
            'damaged' => $damaged,
        ];
    }

    /**
     * @param  array{type?: string, id?: int}  $reference
     */
    public function record(
        int $rollId,
        string $type,
        float $metres,
        ?string $reason,
        array $reference,
        ?int $actorId,
        ?string $idempotencyKey = null,
    ): FabricMovement {
        return DB::transaction(function () use ($rollId, $type, $metres, $reason, $reference, $actorId, $idempotencyKey): FabricMovement {
            $roll = FabricRoll::query()->lockForUpdate()->findOrFail($rollId);

            $movement = FabricMovement::query()->create([
                'fabric_roll_id' => $roll->id,
                'branch_id' => $roll->branch_id,
                'type' => $type,
                'metres' => $metres,
                'reason' => $reason,
                'reference_type' => $reference['type'] ?? null,
                'reference_id' => $reference['id'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'actor_id' => $actorId,
                'occurred_at' => now(),
            ]);

            $this->applyToRemaining($roll, $type, $metres);

            return $movement;
        });
    }

    public function recordReserve(FabricRoll $roll, int $orderItemId, float $metres, ?int $actorId, ?string $idempotencyKey = null): FabricMovement
    {
        return $this->record($roll->id, FabricMovement::TYPE_RESERVE, $metres, null, ['type' => 'order_item', 'id' => $orderItemId], $actorId, $idempotencyKey);
    }

    public function recordRelease(FabricRoll $roll, int $orderItemId, float $metres, ?int $actorId, ?string $reason = null): FabricMovement
    {
        return $this->record($roll->id, FabricMovement::TYPE_RELEASE, $metres, $reason, ['type' => 'order_item', 'id' => $orderItemId], $actorId);
    }

    public function recordConsume(FabricRoll $roll, int $orderItemId, float $metres, ?int $actorId): FabricMovement
    {
        return $this->record($roll->id, FabricMovement::TYPE_OUT, $metres, null, ['type' => 'order_item', 'id' => $orderItemId], $actorId);
    }

    private function applyToRemaining(FabricRoll $roll, string $type, float $metres): void
    {
        if (in_array($type, FabricMovement::ADDITIONS, true)) {
            $new = (float) $roll->remaining_metres + $metres;
        } elseif (in_array($type, FabricMovement::DEDUCTIONS, true)) {
            $new = (float) $roll->remaining_metres - $metres;

            if ($new < 0) {
                throw InventoryException::insufficientStock();
            }
        } else {
            // reserve / release are soft holds — remaining is unchanged.
            return;
        }

        $roll->forceFill([
            'remaining_metres' => $new,
            'status' => $new <= 0.0 && $roll->status === FabricRoll::STATUS_ACTIVE
                ? FabricRoll::STATUS_DEPLETED
                : $roll->status,
        ])->save();
    }

    private function sum(int $rollId, string $type): float
    {
        return (float) FabricMovement::query()
            ->where('fabric_roll_id', $rollId)
            ->where('type', $type)
            ->sum('metres');
    }
}
