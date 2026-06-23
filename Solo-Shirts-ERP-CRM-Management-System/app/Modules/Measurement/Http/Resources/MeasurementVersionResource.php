<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Http\Resources;

use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin MeasurementVersion
 */
final class MeasurementVersionResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'profile_id' => $this->profile_id,
            'version_number' => $this->version_number,
            'status' => $this->status,
            'shirt_data' => $this->shirt_data,
            'pant_data' => $this->pant_data,
            'significant_change' => $this->significant_change,
            'diff_json' => $this->diff_json,
            'effective_from' => $this->date($this->effective_from),
            'effective_to' => $this->date($this->effective_to),
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->date($this->approved_at),
            'rejection_reason' => $this->rejection_reason,
        ];
    }
}
