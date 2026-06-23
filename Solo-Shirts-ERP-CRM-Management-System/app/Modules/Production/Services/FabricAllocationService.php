<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Models\User;
use App\Modules\Inventory\Contracts\StockLedgerInterface;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Order\Exceptions\OrderException;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Exceptions\FabricException;
use App\Modules\Production\Models\FabricAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Two-phase fabric allocation. Reserving holds metres against a roll's available
 * pool (not its physical remaining) under a row lock so concurrent reservers
 * cannot oversell the last metres. All stock writes flow through the ledger.
 */
final class FabricAllocationService
{
    public function __construct(
        private readonly StockLedgerInterface $ledger,
        private readonly StateTransitionService $transitions,
    ) {}

    public function reserve(int $itemId, int $rollId, float $metres, User $actor, string $idempotencyKey): FabricAllocation
    {
        return DB::transaction(function () use ($itemId, $rollId, $metres, $actor, $idempotencyKey): FabricAllocation {
            $item = OrderItem::query()->with('order')->findOrFail($itemId);

            // Phase 2.5: an intake order is still being prepared at the Front Desk
            // and must not be actionable by production until it is confirmed.
            if ($item->order?->isIntake()) {
                throw OrderException::notConfirmedForProduction();
            }

            /** @var FabricRoll|null $roll */
            $roll = FabricRoll::query()->lockForUpdate()->find($rollId);

            if ($roll === null) {
                throw FabricException::invalidRoll();
            }

            if (!$roll->isReservable()) {
                throw FabricException::rollNotAvailable();
            }

            $alreadyReserved = FabricAllocation::query()
                ->where('order_item_id', $item->id)
                ->where('status', FabricAllocation::STATUS_RESERVED)
                ->exists();

            if ($alreadyReserved) {
                throw FabricException::alreadyAllocated();
            }

            if ($this->ledger->availableMetres($roll) < $metres) {
                throw FabricException::insufficientStock();
            }

            $allocation = FabricAllocation::query()->create([
                'order_item_id' => $item->id,
                'fabric_roll_id' => $roll->id,
                'branch_id' => $item->branch_id,
                'reserved_metres' => $metres,
                'status' => FabricAllocation::STATUS_RESERVED,
                'reserved_at' => now(),
                'reserved_by' => $actor->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            $this->ledger->recordReserve($roll, $item->id, $metres, $actor->id, $idempotencyKey);

            // Reserving fabric advances a fresh item to FabricAllocated.
            if ((string) $item->state === OrderItem::STATE_DRAFT) {
                $this->transitions->transition(
                    $item->id,
                    OrderItem::STATE_FABRIC_ALLOCATED,
                    $actor,
                    (string) Str::uuid(),
                    'fabric reserved',
                );
            }

            return $allocation->load('roll');
        });
    }

    /**
     * Mark a reservation as consumed from the production workbench, recording the
     * actual metres used. The consumed metres leave stock (an OUT movement) and any
     * unused tail (reserved − consumed) is released back to the available pool, so
     * the reservation is fully resolved. Over-consumption is intentionally NOT
     * allowed here — that path stays in the cutting flow — so the OUT can never
     * exceed what was reserved and stock accounting stays clean.
     */
    public function consume(int $itemId, ?float $actualMetres, User $actor): FabricAllocation
    {
        return DB::transaction(function () use ($itemId, $actualMetres, $actor): FabricAllocation {
            /** @var FabricAllocation|null $allocation */
            $allocation = FabricAllocation::query()
                ->where('order_item_id', $itemId)
                ->where('status', FabricAllocation::STATUS_RESERVED)
                ->lockForUpdate()
                ->first();

            if ($allocation === null) {
                throw FabricException::noActiveReservation();
            }

            $roll = FabricRoll::query()->lockForUpdate()->findOrFail($allocation->fabric_roll_id);

            $reserved = (float) $allocation->reserved_metres;
            $consumed = $actualMetres === null ? $reserved : max(0.0, min($actualMetres, $reserved));

            $this->ledger->recordConsume($roll, $itemId, $consumed, $actor->id);

            $tail = round($reserved - $consumed, 2);
            if ($tail > 0.0) {
                $this->ledger->recordRelease($roll, $itemId, $tail, $actor->id, 'unused after consume');
            }

            $allocation->update([
                'status' => FabricAllocation::STATUS_CONSUMED,
                'consumed_metres' => $consumed,
                'consumed_at' => now(),
                'consumed_by' => $actor->id,
            ]);

            return $allocation->load('roll');
        });
    }

    public function release(int $itemId, ?string $reason, User $actor): FabricAllocation
    {
        return DB::transaction(function () use ($itemId, $reason, $actor): FabricAllocation {
            /** @var FabricAllocation|null $allocation */
            $allocation = FabricAllocation::query()
                ->where('order_item_id', $itemId)
                ->where('status', FabricAllocation::STATUS_RESERVED)
                ->lockForUpdate()
                ->first();

            if ($allocation === null) {
                $consumed = FabricAllocation::query()
                    ->where('order_item_id', $itemId)
                    ->where('status', FabricAllocation::STATUS_CONSUMED)
                    ->exists();

                throw $consumed ? FabricException::releaseAfterConsume() : FabricException::noActiveReservation();
            }

            $roll = FabricRoll::query()->lockForUpdate()->findOrFail($allocation->fabric_roll_id);

            $allocation->update([
                'status' => FabricAllocation::STATUS_RELEASED,
                'released_at' => now(),
                'released_by' => $actor->id,
                'release_reason' => $reason,
            ]);

            $this->ledger->recordRelease($roll, $itemId, (float) $allocation->reserved_metres, $actor->id, $reason);

            return $allocation;
        });
    }
}
