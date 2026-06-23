<?php

declare(strict_types=1);

namespace App\Modules\Identity\Policies;

use App\Models\User;

/**
 * Branch-scoped authorization for managing users. Owner is short-circuited to
 * allow everything by the Gate::before hook, so these methods only describe
 * non-owner (Admin) rules.
 */
final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermissionTo('users.view');
    }

    public function view(User $actor, User $target): bool
    {
        return $this->sameBranch($actor, $target) && $actor->hasPermissionTo('users.view');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermissionTo('users.create');
    }

    public function update(User $actor, User $target): bool
    {
        return $this->sameBranch($actor, $target) && $actor->hasPermissionTo('users.update');
    }

    public function delete(User $actor, User $target): bool
    {
        // Deleting users is Owner-only (handled by Gate::before).
        return false;
    }

    public function assignRole(User $actor, User $target): bool
    {
        return $this->sameBranch($actor, $target) && $actor->hasPermissionTo('roles.assign');
    }

    private function sameBranch(User $actor, User $target): bool
    {
        return $actor->branch_id !== null && $actor->branch_id === $target->branch_id;
    }
}
