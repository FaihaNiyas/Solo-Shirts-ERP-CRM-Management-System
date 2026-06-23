<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Resources;

use App\Modules\Production\Models\ProductionNotification;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin ProductionNotification
 */
final class ProductionNotificationResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'order_item_id' => $this->order_item_id,
            'is_read' => $this->isRead(),
            'read_at' => $this->date($this->read_at),
            'created_at' => $this->date($this->created_at),
        ];
    }
}
