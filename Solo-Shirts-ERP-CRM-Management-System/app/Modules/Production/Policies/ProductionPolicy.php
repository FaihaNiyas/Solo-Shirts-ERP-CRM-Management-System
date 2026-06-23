<?php

declare(strict_types=1);

namespace App\Modules\Production\Policies;

use App\Models\User;
use App\Modules\Order\Models\OrderItem;

/**
 * Production authorization. Branch isolation is enforced by the OrderItem global
 * scope (cross-branch binding 404s); these checks are permission-based. Owner is
 * short-circuited by Gate::before.
 *
 * Each target state maps to a discrete permission, so a role can be granted only
 * the legs of the workflow it owns (e.g. a Tailor may move items into tailoring
 * but not sign off QC).
 */
final class ProductionPolicy
{
    /**
     * @var array<string, string>
     */
    public const TRANSITION_PERMISSIONS = [
        OrderItem::STATE_FABRIC_ALLOCATED => 'production.transition.fabric_allocated',
        OrderItem::STATE_CUTTING => 'production.transition.cutting',
        OrderItem::STATE_TAILORING => 'production.transition.tailoring',
        OrderItem::STATE_KAJA_BUTTON => 'production.transition.kaja',
        OrderItem::STATE_FINISHING => 'production.transition.finishing',
        OrderItem::STATE_QC => 'production.transition.qc',
        OrderItem::STATE_REWORK => 'production.transition.rework',
        OrderItem::STATE_PACKING => 'production.transition.packing',
        OrderItem::STATE_READY_FOR_DELIVERY => 'production.transition.ready_for_delivery',
        OrderItem::STATE_DELIVERED => 'production.transition.delivered',
        OrderItem::STATE_CANCELLED => 'production.transition.cancel',
    ];

    public function viewAny(User $actor): bool
    {
        return $actor->hasPermissionTo('production.view');
    }

    public function view(User $actor): bool
    {
        return $actor->hasPermissionTo('production.view');
    }

    public function transition(User $actor, OrderItem $item, string $toState): bool
    {
        $permission = self::TRANSITION_PERMISSIONS[$toState] ?? null;

        return $permission !== null && $actor->hasPermissionTo($permission);
    }

    // --- Phase 8: cutting & fabric allocation (all act on an order item) ---

    public function viewQueue(User $actor): bool
    {
        return $actor->hasAnyPermission(['fabric.allocate', 'cutting.start', 'cutting.complete']);
    }

    public function allocateFabric(User $actor): bool
    {
        return $actor->hasPermissionTo('fabric.allocate');
    }

    public function releaseFabric(User $actor): bool
    {
        return $actor->hasPermissionTo('fabric.release');
    }

    public function startCutting(User $actor): bool
    {
        return $actor->hasPermissionTo('cutting.start');
    }

    public function completeCutting(User $actor): bool
    {
        return $actor->hasPermissionTo('cutting.complete');
    }

    // --- Phase 7D: final packing (act on an order item) ---

    public function pack(User $actor): bool
    {
        return $actor->hasPermissionTo('production.packing.manage');
    }

    // --- Phase 10: QC & rework (act on an order item) ---

    public function inspect(User $actor): bool
    {
        return $actor->hasPermissionTo('qc.inspect');
    }

    public function reworkOverride(User $actor): bool
    {
        return $actor->hasAnyPermission(['production.rework.override', 'qc.override']);
    }

    // --- Kanban Phase B: issues & on-hold (act on an order item) ---

    public function reportIssue(User $actor): bool
    {
        return $actor->hasPermissionTo('production.issue.report');
    }

    public function resolveIssue(User $actor): bool
    {
        return $actor->hasPermissionTo('production.issue.resolve');
    }

    public function holdItem(User $actor): bool
    {
        return $actor->hasPermissionTo('production.hold.manage');
    }

    // --- Kanban Phase C: section-supervisor assignment ---

    public function assignSupervisor(User $actor): bool
    {
        return $actor->hasPermissionTo('production.supervisor.assign');
    }

    // --- Kanban Phase D: production dashboard ---

    public function viewDashboard(User $actor): bool
    {
        return $actor->hasPermissionTo('production.dashboard.view');
    }
}
