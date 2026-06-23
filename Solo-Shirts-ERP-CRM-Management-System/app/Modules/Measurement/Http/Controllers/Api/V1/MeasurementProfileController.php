<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Measurement\Http\Requests\CreateProfileRequest;
use App\Modules\Measurement\Http\Requests\UpdateProfileRequest;
use App\Modules\Measurement\Http\Resources\MeasurementProfileResource;
use App\Modules\Measurement\Models\MeasurementProfile;
use App\Modules\Measurement\Services\MeasurementService;
use Illuminate\Http\JsonResponse;

final class MeasurementProfileController extends BaseApiController
{
    public function __construct(private readonly MeasurementService $measurements) {}

    public function index(Customer $customer): JsonResponse
    {
        $this->authorize('viewAny', MeasurementProfile::class);

        $profiles = MeasurementProfile::query()
            ->where('customer_id', $customer->id)
            ->with('currentVersion')
            ->orderByDesc('is_default')
            ->get();

        return $this->respond(MeasurementProfileResource::collection($profiles)->resolve());
    }

    public function store(CreateProfileRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('create', MeasurementProfile::class);

        /** @var User $actor */
        $actor = $request->user();
        $profile = $this->measurements->createProfile($customer, $request->validated(), $actor);
        $profile->load('currentVersion');

        return $this->respond((new MeasurementProfileResource($profile))->resolve(), 'Measurement profile created', 201);
    }

    public function update(UpdateProfileRequest $request, MeasurementProfile $profile): JsonResponse
    {
        $this->authorize('update', $profile);

        $profile = $this->measurements->updateProfile($profile, $request->validated());
        $profile->load('currentVersion');

        return $this->respond((new MeasurementProfileResource($profile))->resolve(), 'Measurement profile updated');
    }

    public function destroy(MeasurementProfile $profile): JsonResponse
    {
        $this->authorize('delete', $profile);

        $this->measurements->deleteProfile($profile);

        return $this->respond(null, 'Measurement profile deleted');
    }
}
