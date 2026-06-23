<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Identity\Http\Requests\AssignRoleRequest;
use App\Modules\Identity\Http\Requests\CreateUserRequest;
use App\Modules\Identity\Http\Requests\UpdateUserRequest;
use App\Modules\Identity\Http\Resources\UserResource;
use App\Modules\Identity\Services\UserService;
use Illuminate\Http\JsonResponse;

final class UserController extends BaseApiController
{
    public function __construct(private readonly UserService $users) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return $this->respond(UserResource::collection($this->users->list())->resolve());
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return $this->respond((new UserResource($user))->resolve());
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validated();

        // A user must belong to a branch (role assignment is team-scoped to it).
        // When the caller doesn't pick one — e.g. an Owner whose context spans all
        // branches — fall back to the actor's own branch rather than null.
        if (empty($data['branch_id'])) {
            $data['branch_id'] = $request->user()?->branch_id;
        }

        $user = $this->users->create($data);

        return $this->respond((new UserResource($user))->resolve(), 'User created', 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user = $this->users->update($user, $request->validated());

        return $this->respond((new UserResource($user))->resolve(), 'User updated');
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return $this->respond(null, 'User deleted');
    }

    public function assignRole(AssignRoleRequest $request, User $user): JsonResponse
    {
        $this->authorize('assignRole', $user);

        $user = $this->users->assignRole($user, (string) $request->string('role'));

        return $this->respond((new UserResource($user))->resolve(), 'Role assigned');
    }

    public function deactivate(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user = $this->users->setActive($user, false);

        return $this->respond((new UserResource($user))->resolve(), 'User deactivated');
    }

    public function activate(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user = $this->users->setActive($user, true);

        return $this->respond((new UserResource($user))->resolve(), 'User reactivated');
    }
}
