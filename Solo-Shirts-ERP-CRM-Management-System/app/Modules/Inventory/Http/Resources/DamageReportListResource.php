<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin DamageReport
 */
final class DamageReportListResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fabric_roll_id' => $this->fabric_roll_id,
            'stage' => $this->stage,
            'damage_type' => $this->damage_type,
            'quantity_lost_metres' => $this->quantity_lost_metres,
            'status' => $this->status,
            'reported_at' => $this->date($this->reported_at),
        ];
    }
}
