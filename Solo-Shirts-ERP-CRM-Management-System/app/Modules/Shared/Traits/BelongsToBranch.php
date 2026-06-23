<?php

declare(strict_types=1);

namespace App\Modules\Shared\Traits;

use App\Modules\Identity\Models\Branch;
use App\Modules\Shared\Scopes\BranchScope;
use App\Modules\Shared\Services\BranchContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Applied to every transactional model. Adds the branch isolation global scope,
 * stamps the active branch on creation when one isn't supplied, and exposes the
 * branch relation.
 *
 * @mixin Model
 */
trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('branch_id') === null) {
                $branchId = app(BranchContext::class)->current();

                if ($branchId !== null) {
                    $model->setAttribute('branch_id', $branchId);
                }
            }
        });
    }

    public function initializeBelongsToBranch(): void
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
