<?php

declare(strict_types=1);

namespace App\Modules\Identity\Policies;

use App\Models\User;

/**
 * Branch management. Creating/updating branches is Owner-only (Gate::before);
 * Admins may only view.
 */
final class BranchPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermissionTo('branches.view');
    }

    public function create(User $actor): bool
    {
        return false; // Owner-only via Gate::before
    }

    public function update(User $actor): bool
    {
        return false; // Owner-only via Gate::before
    }
}
