<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Resources;

use App\Modules\Production\Models\ProductionTransition;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin ProductionTransition
 */
final class ProductionTransitionResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_item_id' => $this->order_item_id,
            'from_state' => $this->from_state,
            'to_state' => $this->to_state,
            'actor_id' => $this->actor_id,
            'actor_name' => $this->whenLoaded('actor', fn () => $this->actor?->name),
            'notes' => $this->notes,
            'completed_qty' => $this->completed_qty,
            'rejected_qty' => $this->rejected_qty,
            'attachment_path' => $this->attachment_path,
            'metadata' => $this->metadata,
            'occurred_at' => $this->date($this->occurred_at),
        ];
    }
}
