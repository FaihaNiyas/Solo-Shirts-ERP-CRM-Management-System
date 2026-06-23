<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Resources;

use App\Modules\Production\Models\TailorAssignment;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin TailorAssignment
 */
final class AssignmentListResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bundle_id' => $this->bundle_id,
            'order_item_id' => $this->order_item_id,
            'tailor_id' => $this->tailor_id,
            'status' => $this->status,
            'assigned_at' => $this->date($this->assigned_at),
            'completed_at' => $this->date($this->completed_at),
        ];
    }
}
