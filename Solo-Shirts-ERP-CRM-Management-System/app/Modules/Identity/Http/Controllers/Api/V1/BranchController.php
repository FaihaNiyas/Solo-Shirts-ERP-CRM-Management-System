<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Identity\Http\Requests\CreateBranchRequest;
use App\Modules\Identity\Http\Requests\UpdateBranchRequest;
use App\Modules\Identity\Http\Resources\BranchResource;
use App\Modules\Identity\Models\Branch;
use App\Modules\Identity\Services\BranchService;
use Illuminate\Http\JsonResponse;

final class BranchController extends BaseApiController
{
    public function __construct(private readonly BranchService $branches) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Branch::class);

        return $this->respond(
            BranchResource::collection($this->branches->list())->resolve()
        );
    }

    /**
     * Active branches only — used to populate the user-creation branch dropdown.
     * Readable by any authenticated user who may create users.
     */
    public function activeList(): JsonResponse
    {
        $this->authorize('viewAny', Branch::class);

        return $this->respond(
            BranchResource::collection($this->branches->activeList())->resolve()
        );
    }

    public function store(CreateBranchRequest $request): JsonResponse
    {
        $this->authorize('create', Branch::class);

        $branch = $this->branches->create($request->validated());

        return $this->respond((new BranchResource($branch))->resolve(), 'Branch created', 201);
    }

    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        $this->authorize('update', $branch);

        $branch = $this->branches->update($branch, $request->validated());

        return $this->respond((new BranchResource($branch))->resolve(), 'Branch updated');
    }

    public function activate(Branch $branch): JsonResponse
    {
        $this->authorize('update', $branch);

        $branch = $this->branches->setActive($branch, true);

        return $this->respond((new BranchResource($branch))->resolve(), 'Branch activated');
    }

    public function deactivate(Branch $branch): JsonResponse
    {
        $this->authorize('update', $branch);

        $branch = $this->branches->setActive($branch, false);

        return $this->respond((new BranchResource($branch))->resolve(), 'Branch deactivated');
    }
}
