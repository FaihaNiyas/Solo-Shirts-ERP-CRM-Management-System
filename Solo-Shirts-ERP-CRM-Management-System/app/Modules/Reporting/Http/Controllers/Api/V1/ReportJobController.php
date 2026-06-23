<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Printing\Http\Resources\DocumentResource;
use App\Modules\Reporting\Exceptions\ReportingException;
use App\Modules\Reporting\Http\Resources\ReportJobResource;
use App\Modules\Reporting\Models\ReportJob;
use Illuminate\Http\JsonResponse;

final class ReportJobController extends BaseApiController
{
    public function show(ReportJob $reportJob): JsonResponse
    {
        $this->authorize('viewReports', ReportJob::class);

        return $this->respond((new ReportJobResource($reportJob->load('document')))->resolve());
    }

    public function download(ReportJob $reportJob): JsonResponse
    {
        $this->authorize('viewReports', ReportJob::class);

        if ($reportJob->status !== ReportJob::STATUS_SUCCEEDED || $reportJob->document === null) {
            throw ReportingException::notReady();
        }

        // The document carries a fresh 10-minute signed download URL.
        return $this->respond((new DocumentResource($reportJob->document))->resolve());
    }
}
