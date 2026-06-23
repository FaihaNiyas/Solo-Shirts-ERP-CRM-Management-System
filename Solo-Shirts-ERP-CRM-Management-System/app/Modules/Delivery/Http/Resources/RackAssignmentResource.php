<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Resources;

use App\Modules\Delivery\Models\RackAssignment;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin RackAssignment
 */
final class RackAssignmentResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rack_slot_id' => $this->rack_slot_id,
            'order_item_id' => $this->order_item_id,
            'assigned_at' => $this->date($this->assigned_at),
            'assigned_by' => $this->assigned_by,
            'released_at' => $this->date($this->released_at),
            'released_by' => $this->released_by,
            'release_reason' => $this->release_reason,
        ];
    }
}
