<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Policies;

use App\Models\User;

/**
 * Authorization for measurement profiles and versions. Branch isolation is
 * enforced by the global scope (cross-branch route binding 404s); these checks
 * are permission-based. Owner is short-circuited by Gate::before.
 */
final class MeasurementPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermissionTo('measurements.view');
    }

    public function view(User $actor): bool
    {
        return $actor->hasPermissionTo('measurements.view');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermissionTo('measurements.create');
    }

    public function update(User $actor): bool
    {
        return $actor->hasPermissionTo('measurements.create');
    }

    public function delete(User $actor): bool
    {
        return $actor->hasPermissionTo('measurements.create');
    }

    public function approve(User $actor): bool
    {
        return $actor->hasPermissionTo('measurements.approve');
    }

    public function reject(User $actor): bool
    {
        return $actor->hasPermissionTo('measurements.reject');
    }
}
