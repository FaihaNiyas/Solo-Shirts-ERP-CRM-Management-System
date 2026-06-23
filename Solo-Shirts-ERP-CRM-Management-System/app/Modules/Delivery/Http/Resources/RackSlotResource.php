<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Resources;

use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin RackSlot
 */
final class RackSlotResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slot_code' => $this->slot_code,
            'label' => $this->label,
            'is_active' => $this->is_active,
            'is_occupied' => $this->isOccupied(),
            'current_order_item_id' => $this->current_order_item_id,
            'occupied_at' => $this->date($this->occupied_at),
        ];
    }
}
