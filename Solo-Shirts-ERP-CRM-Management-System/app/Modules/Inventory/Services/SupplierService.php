<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Models\User;
use App\Modules\Inventory\Models\Supplier;

/**
 * Supplier CRUD. Branch isolation is handled by the BelongsToBranch scope.
 */
final class SupplierService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Supplier
    {
        return Supplier::query()->create([
            'branch_id' => $actor->branch_id,
            'code' => $data['code'],
            'name' => $data['name'],
            'gstin' => $data['gstin'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->fill([
            'name' => $data['name'] ?? $supplier->name,
            'gstin' => $data['gstin'] ?? $supplier->gstin,
            'phone' => $data['phone'] ?? $supplier->phone,
            'email' => $data['email'] ?? $supplier->email,
            'address' => $data['address'] ?? $supplier->address,
            'payment_terms' => $data['payment_terms'] ?? $supplier->payment_terms,
            'is_active' => $data['is_active'] ?? $supplier->is_active,
        ])->save();

        return $supplier;
    }
}
