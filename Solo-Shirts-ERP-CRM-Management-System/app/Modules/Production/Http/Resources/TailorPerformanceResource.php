<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Resources;

use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Wraps the computed performance array from TailorPerformanceService.
 */
final class TailorPerformanceResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $metrics */
        $metrics = $this->resource;

        return $metrics;
    }
}
