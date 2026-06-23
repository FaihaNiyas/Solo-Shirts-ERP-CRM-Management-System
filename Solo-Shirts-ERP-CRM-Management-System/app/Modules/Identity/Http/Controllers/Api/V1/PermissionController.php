<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Identity\Http\Requests\StorePermissionRequest;
use App\Modules\Identity\Http\Requests\UpdatePermissionRequest;
use App\Modules\Identity\Http\Resources\PermissionResource;
use App\Modules\Shared\Support\ApiResponse;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission CRUD. Permissions are global (no team scope). System permissions
 * (the seeded set in RolePermissionSeeder::PERMISSIONS) are referenced by code
 * and policies, so they may not be renamed or deleted — only custom permissions
 * can. Deleting a permission detaches it from every role.
 */
final class PermissionController extends BaseApiController
{
    public function __construct(private readonly PermissionRegistrar $registrar) {}

    private function globalContext(): void
    {
        $this->registrar->setPermissionsTeamId(null);
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless((bool) $request->user()?->can('permissions.view'), 403);
        $this->globalContext();

        $permissions = Permission::query()->withCount('roles')->orderBy('name')->get();

        return $this->respond(PermissionResource::collection($permissions)->resolve());
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $this->globalContext();
        $permission = Permission::create(['name' => $request->string('name'), 'guard_name' => 'web']);
        $this->registrar->forgetCachedPermissions();

        return $this->respond((new PermissionResource($permission))->resolve(), 'Permission created', 201);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $this->globalContext();

        if (in_array($permission->name, RolePermissionSeeder::PERMISSIONS, true)) {
            return ApiResponse::error(
                message: 'System permissions cannot be renamed.',
                code: 'SYSTEM_PERMISSION_IMMUTABLE',
                status: 422,
            );
        }

        $permission->name = $request->string('name');
        $permission->save();
        $this->registrar->forgetCachedPermissions();

        return $this->respond((new PermissionResource($permission))->resolve(), 'Permission updated');
    }

    public function destroy(Request $request, Permission $permission): JsonResponse
    {
        abort_unless((bool) $request->user()?->can('permissions.manage'), 403);
        $this->globalContext();

        if (in_array($permission->name, RolePermissionSeeder::PERMISSIONS, true)) {
            return ApiResponse::error(
                message: 'System permissions cannot be deleted.',
                code: 'SYSTEM_PERMISSION_IMMUTABLE',
                status: 422,
            );
        }

        $permission->delete();
        $this->registrar->forgetCachedPermissions();

        return $this->respond(null, 'Permission deleted');
    }
}
