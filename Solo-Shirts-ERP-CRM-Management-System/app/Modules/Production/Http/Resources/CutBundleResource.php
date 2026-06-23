<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Resources;

use App\Modules\Production\Models\CutBundle;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin CutBundle
 */
final class CutBundleResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_item_id' => $this->order_item_id,
            'fabric_roll_id' => $this->fabric_roll_id,
            'bundle_code' => $this->bundle_code,
            'pieces_count' => $this->pieces_count,
            'notes' => $this->notes,
            'created_at' => $this->date($this->created_at),
        ];
    }
}
