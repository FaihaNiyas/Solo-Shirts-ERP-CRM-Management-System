<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Resources;

use App\Modules\Production\Models\QcInspection;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin QcInspection
 */
final class QcInspectionResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_item_id' => $this->order_item_id,
            'attempt_number' => $this->attempt_number,
            'previous_inspection_id' => $this->previous_inspection_id,
            'disposition' => $this->disposition,
            'result' => $this->result(),
            'failure_reason' => $this->failure_reason,
            'failure_stage' => $this->failure_stage,
            'rework_target_stage' => $this->rework_target_stage,
            'inspector_id' => $this->inspector_id,
            'inspector_name' => $this->whenLoaded('inspector', fn () => $this->inspector?->name),
            'notes' => $this->notes,
            'inspected_at' => $this->date($this->inspected_at),
            'defects' => QcDefectResource::collection($this->whenLoaded('defects', fn () => $this->defects, collect()))->resolve(),
        ];
    }
}
