<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Resources;

use App\Modules\Reporting\Models\NotificationMessage;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin NotificationMessage
 */
final class NotificationResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'status' => $this->status,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'attempt_count' => $this->attempt_count,
            'sent_at' => $this->date($this->sent_at),
        ];
    }
}
