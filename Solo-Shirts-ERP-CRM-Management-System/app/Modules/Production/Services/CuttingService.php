<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Models\User;
use App\Modules\Inventory\Contracts\StockLedgerInterface;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Exceptions\FabricException;
use App\Modules\Production\Models\CutBundle;
use App\Modules\Production\Models\FabricAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Drives the cutting leg: begin cutting (state move) and complete cutting
 * (consume fabric, mint labelled bundles, advance to Tailoring). State moves
 * go through the Phase 7 engine; stock writes go through the ledger.
 */
final class CuttingService
{
    public function __construct(
        private readonly StockLedgerInterface $ledger,
        private readonly StateTransitionService $transitions,
    ) {}

    public function startCutting(int $itemId, User $actor): OrderItem
    {
        $item = OrderItem::query()->findOrFail($itemId);

        $hasReservation = FabricAllocation::query()
            ->where('order_item_id', $item->id)
            ->where('status', FabricAllocation::STATUS_RESERVED)
            ->exists();

        if (!$hasReservation) {
            throw FabricException::noActiveReservation();
        }

        // The machine enforces FabricAllocated → Cutting (rejects a raw Draft).
        $this->transitions->transition(
            $item->id,
            OrderItem::STATE_CUTTING,
            $actor,
            (string) Str::uuid(),
            'cutting started',
        );

        return $item->refresh();
    }

    /**
     * @param  list<array{pieces: int, notes?: string|null}>  $bundles
     * @return array{item: OrderItem, bundles: list<CutBundle>}
     */
    public function completeCutting(int $itemId, float $actualMetres, array $bundles, User $actor): array
    {
        return DB::transaction(function () use ($itemId, $actualMetres, $bundles, $actor): array {
            $item = OrderItem::query()->findOrFail($itemId);

            /** @var FabricAllocation|null $allocation */
            $allocation = FabricAllocation::query()
                ->where('order_item_id', $item->id)
                ->where('status', FabricAllocation::STATUS_RESERVED)
                ->lockForUpdate()
                ->first();

            if ($allocation === null) {
                throw FabricException::noActiveReservation();
            }

            $roll = FabricRoll::query()->lockForUpdate()->findOrFail($allocation->fabric_roll_id);
            $reserved = (float) $allocation->reserved_metres;

            if ($actualMetres > $reserved) {
                if (!$actor->can('fabric.over_consume')) {
                    throw FabricException::overConsumeForbidden();
                }

                if ((float) $roll->remaining_metres < $actualMetres) {
                    throw FabricException::insufficientStock();
                }
            }

            $this->ledger->recordConsume($roll, $item->id, $actualMetres, $actor->id);

            // Return the unused tail of the reservation to available stock.
            if ($actualMetres < $reserved) {
                $this->ledger->recordRelease($roll, $item->id, $reserved - $actualMetres, $actor->id, 'cutting leftover');
            }

            $allocation->update([
                'status' => FabricAllocation::STATUS_CONSUMED,
                'consumed_metres' => $actualMetres,
                'consumed_at' => now(),
                'consumed_by' => $actor->id,
            ]);

            $created = [];

            foreach ($bundles as $index => $bundle) {
                $code = $item->item_code . '-B' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);

                $created[] = CutBundle::query()->create([
                    'order_item_id' => $item->id,
                    'fabric_roll_id' => $roll->id,
                    'branch_id' => $item->branch_id,
                    'bundle_code' => $code,
                    'pieces_count' => $bundle['pieces'],
                    'notes' => $bundle['notes'] ?? null,
                    'created_by' => $actor->id,
                ]);
            }

            $this->transitions->transition(
                $item->id,
                OrderItem::STATE_TAILORING,
                $actor,
                (string) Str::uuid(),
                'cutting completed',
            );

            return ['item' => $item->refresh(), 'bundles' => $created];
        });
    }
}
