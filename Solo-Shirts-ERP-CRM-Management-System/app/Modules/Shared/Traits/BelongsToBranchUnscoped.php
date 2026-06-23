<?php

declare(strict_types=1);

namespace App\Modules\Shared\Traits;

use App\Modules\Identity\Models\Branch;
use App\Modules\Shared\Services\BranchContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Like BelongsToBranch, but WITHOUT the branch-isolation global scope. Records the
 * registering branch on `branch_id` (stamped on create, exposed via branch()) for
 * provenance — "which branch first registered this" — yet leaves the record
 * visible across all branches.
 *
 * Used for shared customer-domain data (customers and their measurement profiles
 * / versions): a customer can be served at any branch, so their record is global;
 * the ORDER they place is still branch-scoped to the branch that took it.
 *
 * @mixin Model
 */
trait BelongsToBranchUnscoped
{
    public static function bootBelongsToBranchUnscoped(): void
    {
        static::creating(function (Model $model): void {
            if ($model->getAttribute('branch_id') === null) {
                $branchId = app(BranchContext::class)->current();

                if ($branchId !== null) {
                    $model->setAttribute('branch_id', $branchId);
                }
            }
        });
    }

    public function initializeBelongsToBranchUnscoped(): void
    {
        $this->mergeCasts(['branch_id' => 'integer']);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
