<?php

declare(strict_types=1);

namespace App\Modules\Customer\Database\Factories;

use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\FamilyMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FamilyMember>
 */
final class FamilyMemberFactory extends Factory
{
    protected $model = FamilyMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'name' => fake()->firstName(),
            'relation' => fake()->randomElement(['son', 'daughter', 'spouse', 'father', 'mother']),
            'dob' => fake()->date(),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'notes' => null,
        ];
    }
}
