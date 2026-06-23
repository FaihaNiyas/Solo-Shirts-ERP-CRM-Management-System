<?php

declare(strict_types=1);

namespace App\Modules\Production\Policies;

use App\Models\User;

/**
 * Defect categories are a global managed list. Inspectors may read them; only
 * managers may create/maintain them.
 */
final class DefectCategoryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasAnyPermission(['qc.inspect', 'qc.defect_categories.manage']);
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermissionTo('qc.defect_categories.manage');
    }
}
