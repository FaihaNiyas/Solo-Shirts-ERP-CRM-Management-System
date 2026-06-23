<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\PurchaseOrderItem;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin PurchaseOrder
 */
final class PoResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'po_code' => $this->po_code,
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->whenLoaded('supplier', fn () => $this->supplier?->name),
            'status' => $this->status,
            'total_paise' => $this->total_paise,
            'notes' => $this->notes,
            'placed_at' => $this->date($this->placed_at),
            'items' => $this->items->map(fn (PurchaseOrderItem $item): array => [
                'id' => $item->id,
                'fabric_type_id' => $item->fabric_type_id,
                'colour' => $item->colour,
                'quantity_metres' => $item->quantity_metres,
                'unit_price_paise' => $item->unit_price_paise,
                'received_metres' => $item->received_metres,
            ])->all(),
        ];
    }
}
