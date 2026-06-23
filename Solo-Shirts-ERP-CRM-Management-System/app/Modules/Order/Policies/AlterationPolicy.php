<?php

declare(strict_types=1);

namespace App\Modules\Order\Policies;

use App\Models\User;
use App\Modules\Order\Models\AlterationRequest;

/**
 * Customer post-delivery alteration access. Narrow on purpose. Front Desk may
 * intake, view, and (at handover) mark delivered. Driving the workflow —
 * approve / start / ready / cancel — needs alterations.update (Admin /
 * Production Supervisor). It grants no production, QC, or finance rights.
 */
final class AlterationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('alterations.view');
    }

    public function view(User $user, AlterationRequest $alteration): bool
    {
        return $user->can('alterations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('alterations.create');
    }

    /** Drive the alteration workflow (approve / start / ready / cancel). */
    public function update(User $user, AlterationRequest $alteration): bool
    {
        return $user->can('alterations.update');
    }

    /**
     * Mark a ready alteration delivered — the Front-Desk handover step. Full
     * workflow managers (alterations.update) may also do it.
     */
    public function deliver(User $user, AlterationRequest $alteration): bool
    {
        return $user->can('alterations.deliver') || $user->can('alterations.update');
    }
}
