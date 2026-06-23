<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Resources;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderProgressSummary;
use App\Modules\Order\Services\OrderStatusDeriver;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin Order
 */
final class OrderListResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer?->name),
            'source' => $this->source,
            'status' => app(OrderStatusDeriver::class)->derive($this->items),
            // Derived production rollup (aggregate_status_label + counts) — compact,
            // no per-item payload. Items are eager-loaded by the controller.
            'progress' => app(OrderProgressSummary::class)->summarise($this->items),
            'lifecycle_status' => $this->lifecycle_status,
            'item_count' => $this->items->count(),
            // Rupees — invoice total once confirmed, else the intake estimate.
            'total_amount' => $this->resource->computedTotalPaise() / 100,
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'created_at' => $this->date($this->created_at),
        ];
    }
}
