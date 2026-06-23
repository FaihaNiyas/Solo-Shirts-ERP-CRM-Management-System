<?php

declare(strict_types=1);

namespace App\Modules\Customer\Database\Factories;

use App\Modules\Customer\Models\Customer;
use App\Modules\Identity\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Customer>
 */
final class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $phone = fake()->numerify('9#########');

        return [
            'branch_id' => Branch::factory(),
            'customer_code' => 'SSI-TST-' . strtoupper(Str::random(6)),
            'name' => fake()->name(),
            'phone' => $phone,
            'phone_last4' => substr($phone, -4),
            'address' => fake()->address(),
            'special_notes' => null,
        ];
    }
}
