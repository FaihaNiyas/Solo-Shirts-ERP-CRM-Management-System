<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Services\ProductionDashboardService;
use Illuminate\Http\JsonResponse;

/**
 * Live production dashboard (Kanban Phase D). Manager-facing operational summary —
 * per-stage counts, delayed/urgent/on-hold/rework, completed-today, average dwell
 * time per stage and the bottleneck. Branch-scoped.
 */
final class ProductionDashboardController extends BaseApiController
{
    public function __construct(private readonly ProductionDashboardService $dashboard) {}

    public function summary(): JsonResponse
    {
        $this->authorize('viewDashboard', OrderItem::class);

        return $this->respond($this->dashboard->summary());
    }
}
