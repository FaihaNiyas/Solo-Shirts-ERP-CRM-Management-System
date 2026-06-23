<?php

declare(strict_types=1);

namespace App\Modules\Production\Database\Factories;

use App\Modules\Production\Models\DefectCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DefectCategory>
 */
final class DefectCategoryFactory extends Factory
{
    protected $model = DefectCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'code' => Str::slug($name) . '-' . strtolower(Str::random(4)),
            'name' => ucfirst($name),
            'is_active' => true,
        ];
    }
}
