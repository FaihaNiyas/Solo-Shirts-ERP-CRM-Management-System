<?php

declare(strict_types=1);

namespace App\Modules\Customer\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Customer\Http\Requests\CreateFamilyMemberRequest;
use App\Modules\Customer\Http\Requests\UpdateFamilyMemberRequest;
use App\Modules\Customer\Http\Resources\FamilyMemberResource;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\FamilyMember;
use App\Modules\Customer\Services\FamilyMemberService;
use Illuminate\Http\JsonResponse;

final class FamilyMemberController extends BaseApiController
{
    public function __construct(private readonly FamilyMemberService $familyMembers) {}

    public function index(Customer $customer): JsonResponse
    {
        $this->authorize('view', Customer::class);

        $customer->load('familyMembers');

        return $this->respond(FamilyMemberResource::collection($customer->familyMembers)->resolve());
    }

    public function store(CreateFamilyMemberRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('manageFamily', $customer);

        $member = $this->familyMembers->create($customer, $request->validated());

        return $this->respond((new FamilyMemberResource($member))->resolve(), 'Family member added', 201);
    }

    public function update(UpdateFamilyMemberRequest $request, Customer $customer, FamilyMember $familyMember): JsonResponse
    {
        $this->authorize('manageFamily', $customer);

        $member = $this->familyMembers->update($familyMember, $request->validated());

        return $this->respond((new FamilyMemberResource($member))->resolve(), 'Family member updated');
    }

    public function destroy(Customer $customer, FamilyMember $familyMember): JsonResponse
    {
        $this->authorize('manageFamily', $customer);

        $this->familyMembers->delete($familyMember);

        return $this->respond(null, 'Family member removed');
    }
}
