<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Resources;

use App\Modules\Production\Models\ProductionIssue;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin ProductionIssue
 */
final class ProductionIssueResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_item_id' => $this->order_item_id,
            'stage' => $this->stage,
            'issue_type' => $this->issue_type,
            'description' => $this->description,
            'status' => $this->status,
            'reported_by' => $this->reported_by,
            'reporter_name' => $this->whenLoaded('reporter', fn () => $this->reporter?->name),
            'resolved_by' => $this->resolved_by,
            'resolver_name' => $this->whenLoaded('resolver', fn () => $this->resolver?->name),
            'resolved_at' => $this->date($this->resolved_at),
            'resolution_notes' => $this->resolution_notes,
            'created_at' => $this->date($this->created_at),
        ];
    }
}
