<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Database\Factories;

use App\Modules\Identity\Models\Branch;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Inventory\Models\FabricType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FabricRoll>
 */
final class FabricRollFactory extends Factory
{
    protected $model = FabricRoll::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $metres = fake()->randomFloat(2, 20, 100);

        return [
            'branch_id' => Branch::factory(),
            'roll_code' => 'ROLL-' . strtoupper(Str::random(8)),
            'fabric_type_id' => FabricType::factory(),
            'colour' => fake()->safeColorName(),
            'received_length_metres' => $metres,
            'remaining_metres' => $metres,
            'unit_price_paise' => fake()->numberBetween(10_000, 80_000),
            'received_date' => now()->toDateString(),
            'status' => FabricRoll::STATUS_ACTIVE,
        ];
    }

    public function withRemaining(float $metres): static
    {
        return $this->state(fn (array $attributes): array => [
            'received_length_metres' => $metres,
            'remaining_metres' => $metres,
        ]);
    }
}
