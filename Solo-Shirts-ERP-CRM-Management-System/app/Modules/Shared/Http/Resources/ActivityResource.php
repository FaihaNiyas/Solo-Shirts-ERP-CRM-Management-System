<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Resources;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * @mixin Activity
 */
final class ActivityResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'event' => $this->event,
            'description' => $this->description,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'causer_id' => $this->causer_id,
            'causer_name' => $this->whenLoaded('causer', fn () => $this->causer?->getAttribute('name')),
            'properties' => $this->properties,
            'created_at' => $this->date($this->created_at),
        ];
    }
}
