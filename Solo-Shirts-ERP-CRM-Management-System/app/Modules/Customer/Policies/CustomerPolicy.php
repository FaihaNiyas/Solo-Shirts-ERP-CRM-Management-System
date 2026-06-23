<?php

declare(strict_types=1);

namespace App\Modules\Customer\Policies;

use App\Models\User;

/**
 * Customer authorization. The BranchScope already hides other-branch customers
 * (route binding 404s), so these checks are purely permission-based. Owner is
 * short-circuited by Gate::before.
 */
final class CustomerPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermissionTo('customers.view');
    }

    public function view(User $actor): bool
    {
        return $actor->hasPermissionTo('customers.view');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermissionTo('customers.create');
    }

    public function update(User $actor): bool
    {
        return $actor->hasPermissionTo('customers.update');
    }

    public function delete(User $actor): bool
    {
        return $actor->hasPermissionTo('customers.delete');
    }

    public function manageFamily(User $actor): bool
    {
        return $actor->hasPermissionTo('family_members.manage');
    }
}
