<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Database\Factories;

use App\Models\User;
use App\Modules\Identity\Models\Branch;
use App\Modules\Inventory\Models\DamageReportPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DamageReportPhoto>
 */
final class DamageReportPhotoFactory extends Factory
{
    protected $model = DamageReportPhoto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'disk' => 's3',
            'path' => 'damage-reports/' . Str::uuid() . '.jpg',
            'size_bytes' => fake()->numberBetween(10_000, 2_000_000),
            'uploaded_by' => User::factory(),
        ];
    }
}
