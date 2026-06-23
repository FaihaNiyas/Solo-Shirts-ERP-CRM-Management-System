<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Policies;

use App\Models\User;

/**
 * Inventory authorization. Registered against FabricRoll and used class-level for
 * every inventory action (rolls, suppliers, fabric types, POs). Branch isolation
 * is enforced by the BelongsToBranch scopes; Owner bypasses via Gate::before.
 */
final class InventoryPolicy
{
    public function view(User $actor): bool
    {
        return $actor->hasPermissionTo('inventory.view');
    }

    public function createRoll(User $actor): bool
    {
        return $actor->hasPermissionTo('inventory.fabric_rolls.create');
    }

    public function adjustRoll(User $actor): bool
    {
        return $actor->hasPermissionTo('inventory.fabric_rolls.adjust');
    }

    public function manageSuppliers(User $actor): bool
    {
        return $actor->hasPermissionTo('inventory.suppliers.manage');
    }

    public function manageFabricTypes(User $actor): bool
    {
        return $actor->hasPermissionTo('inventory.suppliers.manage');
    }

    public function createPo(User $actor): bool
    {
        return $actor->hasPermissionTo('inventory.purchase_orders.create');
    }

    public function placePo(User $actor): bool
    {
        return $actor->hasPermissionTo('inventory.purchase_orders.place');
    }

    public function receivePo(User $actor): bool
    {
        return $actor->hasPermissionTo('inventory.purchase_orders.receive');
    }

    public function viewLowStock(User $actor): bool
    {
        return $actor->hasPermissionTo('inventory.low_stock.view');
    }
}
