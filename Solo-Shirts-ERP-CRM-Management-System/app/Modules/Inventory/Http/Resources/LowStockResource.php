<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Wraps a low-stock row (a stdClass from the aggregate query).
 */
final class LowStockResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var object $row */
        $row = $this->resource;

        return [
            'branch_id' => (int) $row->branch_id,
            'fabric_type_id' => (int) $row->fabric_type_id,
            'code' => (string) $row->code,
            'name' => (string) $row->name,
            'total_remaining' => number_format((float) $row->total_remaining, 2, '.', ''),
            'threshold' => number_format((float) $row->threshold, 2, '.', ''),
        ];
    }
}
