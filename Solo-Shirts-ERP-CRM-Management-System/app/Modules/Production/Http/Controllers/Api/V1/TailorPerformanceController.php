<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Production\Http\Resources\TailorPerformanceResource;
use App\Modules\Production\Models\TailorAssignment;
use App\Modules\Production\Services\TailorPerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class TailorPerformanceController extends BaseApiController
{
    public function __construct(private readonly TailorPerformanceService $performance) {}

    public function show(Request $request, int $tailor): JsonResponse
    {
        $this->authorize('viewPerformance', TailorAssignment::class);

        $from = $request->filled('from')
            ? Carbon::parse((string) $request->string('from'))->startOfDay()
            : now()->subDays(30)->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse((string) $request->string('to'))->endOfDay()
            : now()->endOfDay();

        $metrics = $this->performance->performance($tailor, $from, $to);

        return $this->respond((new TailorPerformanceResource($metrics))->resolve());
    }
}
