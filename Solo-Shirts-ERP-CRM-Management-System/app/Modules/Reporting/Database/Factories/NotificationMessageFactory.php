<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Database\Factories;

use App\Modules\Identity\Models\Branch;
use App\Modules\Reporting\Models\NotificationMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationMessage>
 */
final class NotificationMessageFactory extends Factory
{
    protected $model = NotificationMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'channel' => NotificationMessage::CHANNEL_EMAIL,
            'recipient' => $this->faker->safeEmail(),
            'payload' => ['template' => 'generic'],
            'status' => NotificationMessage::STATUS_QUEUED,
            'attempt_count' => 0,
        ];
    }
}
