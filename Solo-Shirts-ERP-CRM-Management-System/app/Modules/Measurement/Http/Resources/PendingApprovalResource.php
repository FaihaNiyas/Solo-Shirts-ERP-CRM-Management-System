<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Http\Resources;

use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin MeasurementVersion
 */
final class PendingApprovalResource extends BaseResource
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
            'significant_change' => $this->significant_change,
            'diff_json' => $this->diff_json,
            'created_by' => $this->created_by,
            'created_at' => $this->date($this->created_at),
        ];
    }
}
