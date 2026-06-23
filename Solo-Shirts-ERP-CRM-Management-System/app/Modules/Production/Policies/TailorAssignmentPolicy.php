<?php

declare(strict_types=1);

namespace App\Modules\Production\Policies;

use App\Models\User;
use App\Modules\Production\Models\TailorAssignment;

/**
 * Tailoring assignment authorization. Branch isolation is enforced by the global
 * scope; these checks are permission-based. A tailor may act on their own
 * assignments; a supervisor (tailoring.assign) may act on any.
 */
final class TailorAssignmentPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasAnyPermission([
            'tailoring.assign', 'tailoring.start', 'tailoring.complete', 'tailoring.reassign',
        ]);
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermissionTo('tailoring.assign');
    }

    public function start(User $actor, TailorAssignment $assignment): bool
    {
        return $actor->hasPermissionTo('tailoring.start') && $this->ownsOrSupervises($actor, $assignment);
    }

    public function complete(User $actor, TailorAssignment $assignment): bool
    {
        return $actor->hasPermissionTo('tailoring.complete') && $this->ownsOrSupervises($actor, $assignment);
    }

    public function reassign(User $actor): bool
    {
        return $actor->hasPermissionTo('tailoring.reassign');
    }

    public function viewPerformance(User $actor): bool
    {
        return $actor->hasPermissionTo('tailoring.performance.view');
    }

    private function ownsOrSupervises(User $actor, TailorAssignment $assignment): bool
    {
        return $assignment->tailor_id === $actor->id || $actor->hasPermissionTo('tailoring.assign');
    }
}
