<?php

declare(strict_types=1);

namespace App\Modules\Shared\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Holds the active branch for the current request.
 *
 * - Staff: always their own branch.
 * - Owner: "all branches" (null) by default, or a specific branch once they
 *   switch context (override). Switching makes reads scope to that branch.
 *
 * Phase 3's ResolveBranchContext middleware seeds the override from the token.
 */
final class BranchContext
{
    private ?int $branchId = null;

    private bool $overridden = false;

    /**
     * The branch the current request is scoped to. Null means "all branches"
     * (only ever true for an Owner who has not switched into a branch).
     */
    public function current(): ?int
    {
        if ($this->overridden) {
            return $this->branchId;
        }

        if ($this->isOwner()) {
            return null;
        }

        $user = Auth::user();

        if ($user instanceof Model) {
            $branch = $user->getAttribute('branch_id');

            return $branch !== null ? (int) $branch : null;
        }

        return null;
    }

    public function setCurrent(int $branchId): void
    {
        $this->branchId = $branchId;
        $this->overridden = true;
    }

    /**
     * Owners may cross branch boundaries.
     */
    public function isOwner(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole('Owner');
    }
}
