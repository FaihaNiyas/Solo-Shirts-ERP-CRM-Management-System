<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Inventory\Models\DamageReportPhoto;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * @mixin DamageReport
 */
final class DamageReportResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fabric_roll_id' => $this->fabric_roll_id,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'reported_by' => $this->reported_by,
            'stage' => $this->stage,
            'damage_type' => $this->damage_type,
            'damage_type_other' => $this->damage_type_other,
            'quantity_lost_metres' => $this->quantity_lost_metres,
            'action_taken' => $this->action_taken,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->date($this->approved_at),
            'approval_notes' => $this->approval_notes,
            'rejected_by' => $this->rejected_by,
            'rejected_at' => $this->date($this->rejected_at),
            'rejection_reason' => $this->rejection_reason,
            'reported_at' => $this->date($this->reported_at),
            'photos' => $this->photos->map(fn (DamageReportPhoto $photo): array => [
                'id' => $photo->id,
                'url' => URL::temporarySignedRoute(
                    'damage-reports.photos.download',
                    now()->addMinutes(10),
                    ['photo' => $photo->id],
                ),
                'has_thumbnail' => $photo->thumb_path !== null,
            ])->all(),
        ];
    }
}
