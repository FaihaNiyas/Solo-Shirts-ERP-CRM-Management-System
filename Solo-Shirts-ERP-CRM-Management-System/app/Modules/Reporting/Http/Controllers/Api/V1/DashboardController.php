<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Reporting\Http\Resources\DashboardResource;
use App\Modules\Reporting\Models\ReportJob;
use App\Modules\Reporting\Services\DashboardService;
use App\Modules\Shared\Services\BranchContext;
use Illuminate\Http\JsonResponse;

final class DashboardController extends BaseApiController
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function summary(): JsonResponse
    {
        $this->authorize('viewDashboard', ReportJob::class);

        $branchId = app(BranchContext::class)->current();

        return $this->respond(
            (new DashboardResource($this->dashboard->summary($branchId)))->resolve()
        );
    }
}
