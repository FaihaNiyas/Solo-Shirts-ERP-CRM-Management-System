<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Database\Factories;

use App\Modules\Identity\Models\Branch;
use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PurchaseOrder>
 */
final class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'po_code' => 'SSI-PO-' . strtoupper(Str::random(6)),
            'supplier_id' => Supplier::factory(),
            'status' => PurchaseOrder::STATUS_DRAFT,
            'total_paise' => 0,
        ];
    }

    public function placed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PurchaseOrder::STATUS_PLACED,
            'placed_at' => now(),
        ]);
    }
}
