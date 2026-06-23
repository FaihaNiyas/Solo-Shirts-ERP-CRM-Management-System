<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Identity\Models\Branch;
use Illuminate\Database\Eloquent\Collection;

final class BranchService
{
    /**
     * @return Collection<int, Branch>
     */
    public function list(): Collection
    {
        return Branch::query()->orderBy('code')->get();
    }

    /**
     * Active branches only — feeds the user-creation dropdown.
     *
     * @return Collection<int, Branch>
     */
    public function activeList(): Collection
    {
        return Branch::query()->active()->orderBy('name')->get();
    }

    /**
     * Toggle a branch's active flag. Deactivating removes it from user-creation
     * dropdowns and blocks login for users whose home branch is this one.
     */
    public function setActive(Branch $branch, bool $active): Branch
    {
        $branch->is_active = $active;
        $branch->save();

        return $branch;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Branch
    {
        return Branch::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Branch $branch, array $data): Branch
    {
        $branch->fill($data)->save();

        return $branch;
    }

    /**
     * Persist the Owner's chosen branch on the current token for the session.
     * Does not change the Owner's own branch_id.
     */
    public function switchBranch(User $user, int $branchId): void
    {
        $user->currentAccessToken()->forceFill(['active_branch_id' => $branchId])->save();
    }
}
