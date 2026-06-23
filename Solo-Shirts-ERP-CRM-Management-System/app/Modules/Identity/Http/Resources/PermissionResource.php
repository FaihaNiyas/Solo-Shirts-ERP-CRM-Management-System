<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Resources;

use App\Modules\Shared\Http\Resources\BaseResource;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

/**
 * @mixin Permission
 */
final class PermissionResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // The "group" is the dot-prefix (e.g. orders.create → "orders"),
            // used by the UI to group permissions by module.
            'group' => str_contains((string) $this->name, '.')
                ? explode('.', (string) $this->name)[0]
                : 'general',
            'is_system' => in_array($this->name, RolePermissionSeeder::PERMISSIONS, true),
            'roles_count' => $this->roles_count ?? $this->roles()->count(),
            'created_at' => $this->date($this->created_at),
        ];
    }
}
