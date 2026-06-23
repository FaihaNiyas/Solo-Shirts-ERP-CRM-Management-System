<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Resources;

use App\Modules\Order\Models\OrderItem;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin OrderItem
 */
final class CuttingQueueItemResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'item_code' => $this->item_code,
            'product_type' => $this->product_type,
            'quantity' => $this->quantity,
            'state' => (string) $this->state,
            'fabric_preference_text' => $this->fabric_preference_text,
            'measurement_version_id' => $this->measurement_version_id,
        ];
    }
}
