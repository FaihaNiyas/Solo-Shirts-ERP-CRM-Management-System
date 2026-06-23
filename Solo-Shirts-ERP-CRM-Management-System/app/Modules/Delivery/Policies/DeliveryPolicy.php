<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Policies;

use App\Models\User;

/**
 * Delivery authorization. Registered on Delivery and used class-level. Branch
 * isolation is enforced by the BelongsToBranch scope; Owner bypasses via
 * Gate::before.
 */
final class DeliveryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermissionTo('deliveries.view');
    }

    public function view(User $actor): bool
    {
        return $actor->hasPermissionTo('deliveries.view');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermissionTo('deliveries.create');
    }

    public function dispatch(User $actor): bool
    {
        return $actor->hasPermissionTo('deliveries.dispatch');
    }

    public function confirm(User $actor): bool
    {
        return $actor->hasPermissionTo('deliveries.confirm');
    }

    public function attempt(User $actor): bool
    {
        return $actor->hasPermissionTo('deliveries.attempt');
    }

    public function cancel(User $actor): bool
    {
        return $actor->hasPermissionTo('deliveries.cancel');
    }
}
