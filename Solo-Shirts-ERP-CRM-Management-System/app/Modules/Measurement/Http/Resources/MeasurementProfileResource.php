<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Http\Resources;

use App\Modules\Measurement\Models\MeasurementProfile;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin MeasurementProfile
 */
final class MeasurementProfileResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $current = $this->currentVersion;

        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'customer_id' => $this->customer_id,
            'family_member_id' => $this->family_member_id,
            'name' => $this->name,
            'type' => $this->type,
            'is_default' => $this->is_default,
            'current_version' => $current !== null
                ? (new MeasurementVersionResource($current))->toArray($request)
                : null,
        ];
    }
}
