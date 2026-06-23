<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Database\Factories;

use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Models\DeliveryAttempt;
use App\Modules\Identity\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryAttempt>
 */
final class DeliveryAttemptFactory extends Factory
{
    protected $model = DeliveryAttempt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_id' => Delivery::factory(),
            'branch_id' => Branch::factory(),
            'attempted_at' => now(),
            'reason_code' => DeliveryAttempt::REASON_CUSTOMER_UNAVAILABLE,
            'reason_notes' => null,
        ];
    }
}
