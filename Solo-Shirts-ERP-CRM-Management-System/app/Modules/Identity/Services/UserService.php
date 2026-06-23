<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Shared\Services\BranchContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

final class UserService
{
    public function __construct(private readonly BranchContext $branchContext) {}

    /**
     * @return Collection<int, User>
     */
    public function list(): Collection
    {
        $query = User::query()->with('branch');

        $branchId = $this->branchContext->current();
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $branchId = isset($data['branch_id'])
                ? (int) $data['branch_id']
                : $this->branchContext->current();

            $user = User::query()->create([
                'branch_id' => $branchId,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make((string) $data['password']),
                'is_active' => $data['is_active'] ?? true,
            ]);

            if (!empty($data['role'])) {
                $this->assignRole($user, (string) $data['role']);
            }

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $originalBranch = $user->branch_id;

            $user->fill([
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
                'phone' => $data['phone'] ?? $user->phone,
                'is_active' => $data['is_active'] ?? $user->is_active,
            ]);

            if (!empty($data['password'])) {
                $user->password = Hash::make((string) $data['password']);
            }

            if (isset($data['branch_id'])) {
                $user->branch_id = (int) $data['branch_id'];
            }

            $user->save();

            // A branch move invalidates every existing session.
            if ($user->branch_id !== $originalBranch) {
                $this->revokeTokens($user);
            }

            return $user;
        });
    }

    /**
     * Toggle a user's active flag. Deactivating also revokes every live token so
     * access is lost immediately; reactivating leaves the user to log in afresh.
     */
    public function setActive(User $user, bool $active): User
    {
        return DB::transaction(function () use ($user, $active): User {
            $user->is_active = $active;
            $user->save();

            if (!$active) {
                $this->revokeTokens($user);
            }

            return $user;
        });
    }

    public function assignRole(User $user, string $role): User
    {
        return DB::transaction(function () use ($user, $role): User {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->branch_id);
            $user->syncRoles([$role]);

            // A privilege change must not keep old sessions alive.
            $this->revokeTokens($user);

            return $user;
        });
    }

    public function revokeTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}
