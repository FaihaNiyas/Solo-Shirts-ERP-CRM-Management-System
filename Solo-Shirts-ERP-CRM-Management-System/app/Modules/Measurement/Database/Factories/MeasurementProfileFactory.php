<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Database\Factories;

use App\Modules\Customer\Models\Customer;
use App\Modules\Identity\Models\Branch;
use App\Modules\Measurement\Models\MeasurementProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeasurementProfile>
 */
final class MeasurementProfileFactory extends Factory
{
    protected $model = MeasurementProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'customer_id' => Customer::factory(),
            'family_member_id' => null,
            'name' => fake()->randomElement(['Daily fit', 'Formal', 'Slim']),
            'type' => 'shirt',
            'is_default' => false,
        ];
    }
}
