<?php

declare(strict_types=1);

namespace App\Modules\Identity\Database\Factories;

use App\Modules\Identity\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Branch>
 */
final class BranchFactory extends Factory
{
    protected $model = Branch::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(4)),
            'name' => fake()->company(),
            'address' => fake()->address(),
            'gst_number' => strtoupper(Str::random(15)),
            'phone' => fake()->numerify('9#########'),
            'is_active' => true,
        ];
    }
}
