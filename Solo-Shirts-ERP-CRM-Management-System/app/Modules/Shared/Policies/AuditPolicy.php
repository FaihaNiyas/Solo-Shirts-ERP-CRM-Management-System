<?php

declare(strict_types=1);

namespace App\Modules\Shared\Policies;

use App\Models\User;

/**
 * Audit-trail authorization. Registered on the activitylog Activity model and
 * used class-level. Owner bypasses via Gate::before.
 */
final class AuditPolicy
{
    public function viewActivities(User $actor): bool
    {
        return $actor->hasPermissionTo('audit.view');
    }

    public function viewTransitions(User $actor): bool
    {
        return $actor->hasPermissionTo('audit.transitions.view');
    }
}
