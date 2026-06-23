<?php

declare(strict_types=1);

namespace App\Modules\Order\Policies;

use App\Models\User;

/**
 * Order authorization. Branch isolation is enforced by the global scope
 * (cross-branch route binding 404s); these checks are permission-based. Owner
 * is short-circuited by Gate::before.
 */
final class OrderPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermissionTo('orders.view');
    }

    public function view(User $actor): bool
    {
        return $actor->hasPermissionTo('orders.view');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermissionTo('orders.create');
    }

    public function update(User $actor): bool
    {
        return $actor->hasPermissionTo('orders.update');
    }

    public function cancel(User $actor): bool
    {
        return $actor->hasPermissionTo('orders.cancel');
    }

    public function confirm(User $actor): bool
    {
        return $actor->hasPermissionTo('orders.update');
    }

    public function collectPayment(User $actor): bool
    {
        return $actor->hasPermissionTo('orders.collect_payment');
    }

    public function lookup(User $actor): bool
    {
        return $actor->hasPermissionTo('orders.lookup');
    }

    public function handover(User $actor): bool
    {
        return $actor->hasPermissionTo('orders.handover');
    }

    public function sendNotification(User $actor): bool
    {
        return $actor->hasPermissionTo('orders.notifications.send');
    }

    public function viewNotifications(User $actor): bool
    {
        return $actor->hasPermissionTo('orders.notifications.view');
    }

    public function printJobCard(User $actor): bool
    {
        return $actor->hasPermissionTo('orders.print_job_card');
    }

    public function assignBox(User $actor): bool
    {
        return $actor->hasPermissionTo('boxes.assign');
    }

    public function markPlaced(User $actor): bool
    {
        return $actor->hasPermissionTo('boxes.mark_placed');
    }
}
