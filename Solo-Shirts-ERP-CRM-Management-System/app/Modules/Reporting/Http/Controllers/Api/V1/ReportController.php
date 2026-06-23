<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Reporting\Http\Requests\RunReportRequest;
use App\Modules\Reporting\Http\Resources\ReportJobResource;
use App\Modules\Reporting\Models\ReportJob;
use App\Modules\Reporting\Services\ReportRunner;
use Illuminate\Http\JsonResponse;

final class ReportController extends BaseApiController
{
    public function __construct(private readonly ReportRunner $runner) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewReports', ReportJob::class);

        return $this->respond(['kinds' => $this->runner->kinds()]);
    }

    public function run(RunReportRequest $request): JsonResponse
    {
        $this->authorize('runReports', ReportJob::class);

        /** @var User $actor */
        $actor = $request->user();
        /** @var array<string, mixed> $params */
        $params = $request->validated('params') ?? [];

        $job = $this->runner->request((string) $request->string('kind'), $params, $actor);

        return $this->respond((new ReportJobResource($job))->resolve(), 'Report queued', 202);
    }
}
