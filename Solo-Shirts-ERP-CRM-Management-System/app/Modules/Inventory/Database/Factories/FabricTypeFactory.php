<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Database\Factories;

use App\Modules\Inventory\Models\FabricType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FabricType>
 */
final class FabricTypeFactory extends Factory
{
    protected $model = FabricType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'code' => Str::slug($name) . '-' . strtolower(Str::random(4)),
            'name' => ucfirst($name),
            'low_stock_threshold_metres' => 3,
            'is_active' => true,
        ];
    }

    public function threshold(float $metres): static
    {
        return $this->state(fn (array $attributes): array => ['low_stock_threshold_metres' => $metres]);
    }
}
