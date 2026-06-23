<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Database\Factories;

use App\Modules\Identity\Models\Branch;
use App\Modules\Inventory\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Supplier>
 */
final class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'code' => 'SUP-' . strtoupper(Str::random(6)),
            'name' => fake()->company(),
            'gstin' => strtoupper(Str::random(15)),
            'phone' => fake()->numerify('+9198########'),
            'email' => fake()->safeEmail(),
            'payment_terms' => 'Net 30',
            'is_active' => true,
        ];
    }
}
