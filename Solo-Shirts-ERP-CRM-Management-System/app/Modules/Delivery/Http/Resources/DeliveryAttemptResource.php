<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Resources;

use App\Modules\Delivery\Models\DeliveryAttempt;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin DeliveryAttempt
 */
final class DeliveryAttemptResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'delivery_id' => $this->delivery_id,
            'reason_code' => $this->reason_code,
            'reason_notes' => $this->reason_notes,
            'attempted_at' => $this->date($this->attempted_at),
            'attempted_by' => $this->attempted_by,
        ];
    }
}
