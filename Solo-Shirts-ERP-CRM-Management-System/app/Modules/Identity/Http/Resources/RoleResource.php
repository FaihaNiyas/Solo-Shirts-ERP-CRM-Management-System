<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Resources;

use App\Modules\Shared\Http\Resources\BaseResource;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/**
 * @mixin Role
 */
final class RoleResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // System roles (seeded) cannot be renamed or deleted — only their
            // permission set may be edited.
            'is_system' => in_array($this->name, RolePermissionSeeder::ROLES, true),
            'permissions' => $this->permissions->pluck('name')->values(),
            'users_count' => $this->users_count ?? $this->users()->count(),
            'created_at' => $this->date($this->created_at),
        ];
    }
}
