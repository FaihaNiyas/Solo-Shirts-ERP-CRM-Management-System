<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Models\User;
use App\Modules\Inventory\Contracts\StockLedgerInterface;
use App\Modules\Inventory\Exceptions\InventoryException;
use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Inventory\Models\FabricRoll;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates and adjusts fabric rolls. A roll is created with zero remaining and a
 * `receive` movement brings it to its received length, so the ledger and the
 * cached remaining_metres agree from the very first row.
 */
final class FabricRollService
{
    public function __construct(private readonly StockLedgerInterface $ledger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): FabricRoll
    {
        return DB::transaction(function () use ($data, $actor): FabricRoll {
            $metres = (float) $data['received_length_metres'];
            $rollCode = isset($data['roll_code']) && is_string($data['roll_code'])
                ? $data['roll_code']
                : 'R-' . strtoupper(Str::random(12));

            $roll = FabricRoll::query()->create([
                'branch_id' => $actor->branch_id,
                'roll_code' => $rollCode,
                'fabric_type_id' => $data['fabric_type_id'],
                'colour' => $data['colour'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'received_length_metres' => $metres,
                'remaining_metres' => 0,
                'unit_price_paise' => $data['unit_price_paise'] ?? null,
                'received_date' => $data['received_date'] ?? now()->toDateString(),
                'rack_location' => $data['rack_location'] ?? null,
                'status' => FabricRoll::STATUS_ACTIVE,
            ]);

            $this->ledger->record(
                $roll->id,
                FabricMovement::TYPE_RECEIVE,
                $metres,
                'initial receipt',
                ['type' => 'fabric_roll', 'id' => $roll->id],
                $actor->id,
            );

            return $roll->refresh();
        });
    }

    public function adjust(int $rollId, string $type, float $metres, ?string $reason, User $actor): FabricRoll
    {
        $roll = FabricRoll::query()->findOrFail($rollId);

        if ($roll->status === FabricRoll::STATUS_WRITTEN_OFF) {
            throw InventoryException::rollWrittenOff();
        }

        if ($type === FabricMovement::TYPE_ADJUST_OUT && !$actor->can('inventory.fabric_rolls.adjust_out_approve')) {
            throw InventoryException::approvalRequired();
        }

        $this->ledger->record(
            $roll->id,
            $type,
            $metres,
            $reason,
            ['type' => 'manual_adjustment', 'id' => $roll->id],
            $actor->id,
        );

        return $roll->refresh();
    }
}
