<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps the DashboardService rollup summary.
 *
 * @property array{range_days: int, orders_received: int, orders_delivered: int, revenue_paise: int, defects: int} $resource
 */
final class DashboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'range_days' => $this->resource['range_days'],
            'orders_received' => $this->resource['orders_received'],
            'orders_delivered' => $this->resource['orders_delivered'],
            'revenue_paise' => $this->resource['revenue_paise'],
            'defects' => $this->resource['defects'],
        ];
    }
}
