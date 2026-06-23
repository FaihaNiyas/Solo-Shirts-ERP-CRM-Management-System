<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Measurement\Http\Requests\CreateVersionRequest;
use App\Modules\Measurement\Http\Resources\MeasurementVersionResource;
use App\Modules\Measurement\Http\Resources\PendingApprovalResource;
use App\Modules\Measurement\Models\MeasurementProfile;
use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Measurement\Services\MeasurementService;
use Illuminate\Http\JsonResponse;

final class MeasurementVersionController extends BaseApiController
{
    public function __construct(private readonly MeasurementService $measurements) {}

    public function index(MeasurementProfile $profile): JsonResponse
    {
        $this->authorize('viewAny', MeasurementVersion::class);

        return $this->respond(
            MeasurementVersionResource::collection($profile->versions()->orderByDesc('version_number')->get())->resolve()
        );
    }

    public function store(CreateVersionRequest $request, MeasurementProfile $profile): JsonResponse
    {
        $this->authorize('create', MeasurementVersion::class);

        /** @var User $actor */
        $actor = $request->user();
        $version = $this->measurements->createVersion($profile, $request->validated(), $actor);

        return $this->respond((new MeasurementVersionResource($version))->resolve(), 'Measurement version created', 201);
    }

    public function show(MeasurementVersion $version): JsonResponse
    {
        $this->authorize('view', MeasurementVersion::class);

        return $this->respond((new MeasurementVersionResource($version))->resolve());
    }

    public function pendingApproval(): JsonResponse
    {
        $this->authorize('approve', MeasurementVersion::class);

        $pending = MeasurementVersion::query()
            ->where('status', MeasurementVersion::STATUS_PENDING)
            ->orderBy('created_at')
            ->get();

        return $this->respond(PendingApprovalResource::collection($pending)->resolve());
    }
}
