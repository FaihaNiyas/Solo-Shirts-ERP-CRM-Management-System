<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Resources;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Printable job-card payload (customer + items + measurements). Phase 16 renders
 * this into a PDF; here it is the structured source data.
 *
 * @mixin Order
 */
final class JobCardResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'order_code' => $this->order_code,
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'customer' => [
                'name' => $this->customer->name,
                'customer_code' => $this->customer->customer_code,
            ],
            'items' => $this->items->map(function (OrderItem $item): array {
                $version = $item->measurementVersion;

                return [
                    'item_code' => $item->item_code,
                    'product_type' => $item->product_type,
                    'quantity' => $item->quantity,
                    'fabric_preference_text' => $item->fabric_preference_text,
                    'state' => $item->state,
                    'measurements' => [
                        'shirt_data' => $version?->shirt_data,
                        'pant_data' => $version?->pant_data,
                    ],
                ];
            })->all(),
        ];
    }
}
