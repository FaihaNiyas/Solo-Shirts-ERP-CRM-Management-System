<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Policies;

use App\Models\User;

/**
 * Rack authorization. Registered on RackSlot and used class-level. Branch
 * isolation is enforced by the BelongsToBranch scope; Owner bypasses via
 * Gate::before.
 */
final class RackPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermissionTo('rack.view');
    }

    public function view(User $actor): bool
    {
        return $actor->hasPermissionTo('rack.view');
    }

    public function manage(User $actor): bool
    {
        return $actor->hasPermissionTo('rack.slots.manage');
    }

    public function assign(User $actor): bool
    {
        return $actor->hasPermissionTo('rack.assign');
    }

    public function release(User $actor): bool
    {
        return $actor->hasPermissionTo('rack.release');
    }
}
