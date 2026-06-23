<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Measurement\Http\Requests\RejectVersionRequest;
use App\Modules\Measurement\Http\Resources\MeasurementVersionResource;
use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Measurement\Services\MeasurementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MeasurementApprovalController extends BaseApiController
{
    public function __construct(private readonly MeasurementService $measurements) {}

    public function approve(Request $request, MeasurementVersion $version): JsonResponse
    {
        $this->authorize('approve', $version);

        /** @var User $actor */
        $actor = $request->user();
        $version = $this->measurements->approve($version, $actor);

        return $this->respond((new MeasurementVersionResource($version))->resolve(), 'Measurement version approved');
    }

    public function reject(RejectVersionRequest $request, MeasurementVersion $version): JsonResponse
    {
        $this->authorize('reject', $version);

        /** @var User $actor */
        $actor = $request->user();
        $version = $this->measurements->reject($version, (string) $request->string('reason'), $actor);

        return $this->respond((new MeasurementVersionResource($version))->resolve(), 'Measurement version rejected');
    }
}
