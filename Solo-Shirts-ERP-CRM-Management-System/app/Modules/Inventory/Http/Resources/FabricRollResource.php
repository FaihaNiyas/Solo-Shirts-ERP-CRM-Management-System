<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use App\Modules\Inventory\Contracts\StockLedgerInterface;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin FabricRoll
 */
final class FabricRollResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var FabricRoll $roll */
        $roll = $this->resource;

        $b = app(StockLedgerInterface::class)->breakdown($roll);
        $fmt = static fn (float $v): string => number_format($v, 2, '.', '');

        return [
            'id' => $this->id,
            'roll_code' => $this->roll_code,
            'fabric_type_id' => $this->fabric_type_id,
            'colour' => $this->colour,
            'supplier_id' => $this->supplier_id,
            'received_length_metres' => $this->received_length_metres,
            'remaining_metres' => $this->remaining_metres,
            'available_metres' => $fmt($b['available']),
            'reserved_metres' => $fmt($b['reserved']),
            'consumed_metres' => $fmt($b['consumed']),
            'damaged_metres' => $fmt($b['damaged']),
            'low_stock_threshold_metres' => $this->low_stock_threshold_metres,
            'low_stock' => $roll->isLowStock(),
            'unit_price_paise' => $this->unit_price_paise,
            'received_date' => $this->received_date?->toDateString(),
            'rack_location' => $this->rack_location,
            'status' => $this->status,
        ];
    }
}
