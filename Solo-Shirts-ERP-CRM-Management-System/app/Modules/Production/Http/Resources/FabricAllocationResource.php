<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Resources;

use App\Modules\Inventory\Contracts\StockLedgerInterface;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Production\Models\FabricAllocation;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin FabricAllocation
 */
final class FabricAllocationResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var FabricRoll|null $roll */
        $roll = $this->roll;

        return [
            'id' => $this->id,
            'order_item_id' => $this->order_item_id,
            'fabric_roll_id' => $this->fabric_roll_id,
            'reserved_metres' => $this->reserved_metres,
            'consumed_metres' => $this->consumed_metres,
            'status' => $this->status,
            'reserved_at' => $this->date($this->reserved_at),
            'released_at' => $this->date($this->released_at),
            'consumed_at' => $this->date($this->consumed_at),
            'roll' => $roll === null ? null : [
                'id' => $roll->id,
                'roll_code' => $roll->roll_code,
                'remaining_metres' => $roll->remaining_metres,
                'available_metres' => number_format(app(StockLedgerInterface::class)->availableMetres($roll), 2, '.', ''),
            ],
        ];
    }
}
