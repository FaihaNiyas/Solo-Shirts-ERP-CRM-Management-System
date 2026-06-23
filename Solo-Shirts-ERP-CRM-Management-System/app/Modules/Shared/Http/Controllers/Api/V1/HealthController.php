<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Controllers\Api\V1;

use App\Modules\Shared\Services\HealthService;
use App\Modules\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class HealthController
{
    public function __invoke(HealthService $health): JsonResponse
    {
        $snapshot = $health->snapshot();

        $healthy = $snapshot['db'] && $snapshot['redis'] && $snapshot['queue'];

        if ($healthy) {
            return ApiResponse::success($snapshot, 'Service healthy');
        }

        return ApiResponse::error(
            message: 'One or more dependencies are unavailable.',
            code: 'HEALTH_DEPENDENCY_DOWN',
            status: 503,
            data: $snapshot,
        );
    }
}
