<?php

declare(strict_types=1);

namespace App\Modules\Printing\Policies;

use App\Models\User;

/**
 * Document authorization. Registered on Document and used class-level. The
 * download route itself is guarded by a signed URL rather than a policy.
 */
final class DocumentPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermissionTo('documents.view');
    }

    public function view(User $actor): bool
    {
        return $actor->hasPermissionTo('documents.view');
    }

    public function regenerate(User $actor): bool
    {
        return $actor->hasPermissionTo('documents.regenerate');
    }
}
