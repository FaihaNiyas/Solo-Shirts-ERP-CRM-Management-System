<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Resources;

use App\Modules\Delivery\Models\Delivery;
use App\Modules\Finance\Services\BalanceService;
use App\Modules\Order\Services\OrderProgressSummary;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin Delivery
 */
final class DeliveryResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Outstanding balance for the order, so the delivery desk can see — and
        // be blocked by — a pending balance before dispatch/confirm. Source of
        // truth is BalanceService (integer paise), same as the handover gate.
        $outstandingPaise = app(BalanceService::class)->outstandingForOrder($this->order_id)['outstanding_paise'];

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'mode' => $this->mode,
            'status' => $this->status,
            'courier_partner' => $this->courier_partner,
            'tracking_no' => $this->tracking_no,
            'delivery_charges_paise' => $this->delivery_charges_paise,
            'scheduled_at' => $this->date($this->scheduled_at),
            'dispatched_at' => $this->date($this->dispatched_at),
            'completed_at' => $this->date($this->completed_at),
            'outstanding_paise' => $outstandingPaise,
            'balance_amount' => $outstandingPaise / 100,
            'balance_pending' => $outstandingPaise > 0,
            // Parent order production rollup — present only when order.items was
            // eager-loaded (controller index), so list responses stay N+1-free.
            'order_progress' => $this->whenLoaded('order', fn () => $this->order->relationLoaded('items')
                ? app(OrderProgressSummary::class)->summarise($this->order->items)
                : null),
            'attempts' => DeliveryAttemptResource::collection($this->whenLoaded('attempts')),
            'created_at' => $this->date($this->created_at),
        ];
    }
}
