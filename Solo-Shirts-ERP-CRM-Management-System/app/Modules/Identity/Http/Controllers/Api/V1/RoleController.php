<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Identity\Http\Requests\StoreRoleRequest;
use App\Modules\Identity\Http\Requests\UpdateRoleRequest;
use App\Modules\Identity\Http\Resources\RoleResource;
use App\Modules\Shared\Support\ApiResponse;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role CRUD on the global (null-team) Spatie context. Role *definitions* are
 * shared across branches; only role-to-user assignments are branch-scoped, so
 * every action here runs in the null-team context (the request middleware
 * normally sets the team to the caller's branch_id).
 *
 * System roles (the seeded set in RolePermissionSeeder::ROLES) may have their
 * permission set edited but cannot be renamed or deleted — code references them
 * by name (Gate::before, landing routes, guards).
 */
final class RoleController extends BaseApiController
{
    public function __construct(private readonly PermissionRegistrar $registrar) {}

    private function globalContext(): void
    {
        $this->registrar->setPermissionsTeamId(null);
    }

    private function assignedUserCount(int $roleId): int
    {
        return (int) DB::table('model_has_roles')->where('role_id', $roleId)->count();
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless((bool) $request->user()?->can('roles.view'), 403);
        $this->globalContext();

        $roles = Role::query()->with('permissions')->orderBy('name')->get();
        $counts = DB::table('model_has_roles')
            ->select('role_id', DB::raw('count(*) as c'))
            ->groupBy('role_id')->pluck('c', 'role_id');
        $roles->each(fn (Role $r) => $r->users_count = (int) ($counts[$r->id] ?? 0));

        return $this->respond(RoleResource::collection($roles)->resolve());
    }

    public function show(Request $request, Role $role): JsonResponse
    {
        abort_unless((bool) $request->user()?->can('roles.view'), 403);
        $this->globalContext();
        $role->load('permissions');
        $role->users_count = $this->assignedUserCount($role->id);

        return $this->respond((new RoleResource($role))->resolve());
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->globalContext();
        $role = Role::create(['name' => $request->string('name'), 'guard_name' => 'web']);
        $role->syncPermissions($request->input('permissions', []));
        $this->registrar->forgetCachedPermissions();

        return $this->respond((new RoleResource($role->load('permissions')))->resolve(), 'Role created', 201);
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->globalContext();
        $isSystem = in_array($role->name, RolePermissionSeeder::ROLES, true);

        if ($request->filled('name') && $request->string('name')->value() !== $role->name) {
            if ($isSystem) {
                return ApiResponse::error(
                    message: 'System roles cannot be renamed.',
                    code: 'SYSTEM_ROLE_IMMUTABLE',
                    status: 422,
                );
            }
            $role->name = $request->string('name');
            $role->save();
        }

        if ($request->has('permissions')) {
            $role->syncPermissions($request->input('permissions', []));
        }

        $this->registrar->forgetCachedPermissions();
        $role->load('permissions');
        $role->users_count = $this->assignedUserCount($role->id);

        return $this->respond((new RoleResource($role))->resolve(), 'Role updated');
    }

    public function destroy(Request $request, Role $role): JsonResponse
    {
        abort_unless((bool) $request->user()?->can('roles.manage'), 403);
        $this->globalContext();

        if (in_array($role->name, RolePermissionSeeder::ROLES, true)) {
            return ApiResponse::error(
                message: 'System roles cannot be deleted.',
                code: 'SYSTEM_ROLE_IMMUTABLE',
                status: 422,
            );
        }

        if ($this->assignedUserCount($role->id) > 0) {
            return ApiResponse::error(
                message: 'Reassign the users on this role before deleting it.',
                code: 'ROLE_IN_USE',
                status: 422,
            );
        }

        $role->delete();
        $this->registrar->forgetCachedPermissions();

        return $this->respond(null, 'Role deleted');
    }
}
