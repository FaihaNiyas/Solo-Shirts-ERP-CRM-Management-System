<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Resources;

use App\Modules\Production\Models\PackingChecklist;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin PackingChecklist
 */
final class PackingChecklistResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'order_item_id' => $this->order_item_id,
            'checked_measurement_card' => (bool) $this->checked_measurement_card,
            'checked_buttons' => (bool) $this->checked_buttons,
            'checked_ironing' => (bool) $this->checked_ironing,
            'checked_folded' => (bool) $this->checked_folded,
            'checked_packing_cover' => (bool) $this->checked_packing_cover,
            'checked_label' => (bool) $this->checked_label,
            'is_complete' => $this->isComplete(),
            'packed_by' => $this->packed_by,
            'packed_by_name' => $this->whenLoaded('packer', fn () => $this->packer?->name),
            'packed_at' => $this->date($this->packed_at),
            'notes' => $this->notes,
        ];
    }
}
