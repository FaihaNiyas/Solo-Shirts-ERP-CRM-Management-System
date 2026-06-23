<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Policies;

use App\Models\User;

/**
 * Damage report authorization. Approval/rejection is owner-grade (Owner bypasses
 * via Gate::before; Admin holds the permission). Branch isolation is enforced by
 * the BelongsToBranch scope.
 */
final class DamageReportPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermissionTo('damage_reports.view');
    }

    public function view(User $actor): bool
    {
        return $actor->hasPermissionTo('damage_reports.view');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermissionTo('damage_reports.create');
    }

    public function approve(User $actor): bool
    {
        return $actor->hasPermissionTo('damage_reports.approve');
    }

    public function reject(User $actor): bool
    {
        return $actor->hasPermissionTo('damage_reports.reject');
    }
}
