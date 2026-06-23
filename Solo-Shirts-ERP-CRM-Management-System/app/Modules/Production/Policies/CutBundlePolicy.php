<?php

declare(strict_types=1);

namespace App\Modules\Production\Policies;

use App\Models\User;

/**
 * Authorization for cut bundles. Branch isolation is enforced by the global
 * scope; these checks are permission-based. Owner bypasses via Gate::before.
 */
final class CutBundlePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermissionTo('bundles.view');
    }

    public function view(User $actor): bool
    {
        return $actor->hasPermissionTo('bundles.view');
    }
}
