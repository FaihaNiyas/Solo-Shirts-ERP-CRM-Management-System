<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Resources;

use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Services\OrderProgressSummary;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin OrderItem
 */
final class OrderItemResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $state = (string) $this->state;
        $design = is_array($this->design_notes) ? $this->design_notes : [];

        return [
            'id' => $this->id,
            'item_code' => $this->item_code,
            'product_type' => $this->product_type,
            'quantity' => $this->quantity,
            'measurement_version_id' => $this->measurement_version_id,
            'fabric_preference_text' => $this->fabric_preference_text,
            'design_notes' => $this->design_notes,
            'state' => $state,
            // Display-ready, consistent across every screen (single backend mapper).
            'production_state' => $state,
            'production_state_label' => OrderProgressSummary::label($state),
            'fabric_summary' => $design['fabric'] ?? $this->fabric_preference_text,
            'style_summary' => $design['style'] ?? null,
            'fit_summary' => $design['fit'] ?? null,
            'is_ready_for_handover' => $state === OrderItem::STATE_READY_FOR_DELIVERY,
            'is_delivered' => $state === OrderItem::STATE_DELIVERED,
            'cancelled_at' => $this->date($this->cancelled_at),
            'cancel_reason' => $this->cancel_reason,
            // Phase 2 — production box & placement.
            'production_box_id' => $this->production_box_id,
            'box_code' => $this->box_code,
            'placed_in_box' => (bool) $this->placed_in_box,
            'placed_in_box_at' => $this->date($this->placed_in_box_at),
        ];
    }
}
