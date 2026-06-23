<?php

declare(strict_types=1);

namespace App\Modules\Shared\Scopes;

use App\Modules\Shared\Services\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that constrains every query on a branch-owned model to the
 * active branch. BranchContext::current() returns null for an Owner who has not
 * pinned a branch (and in console/unauthenticated contexts), in which case the
 * scope is a no-op so seeders and Owners see everything.
 */
final class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $branchId = app(BranchContext::class)->current();

        if ($branchId !== null) {
            $builder->where($model->getTable() . '.branch_id', $branchId);
        }
    }
}
