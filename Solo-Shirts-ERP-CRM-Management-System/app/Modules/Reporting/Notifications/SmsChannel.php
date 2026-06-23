<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Notifications;

use App\Modules\Reporting\Models\NotificationMessage;

/**
 * SMS channel. The actual gateway call is wired in deployment; here it is a stub
 * that always succeeds.
 */
final class SmsChannel implements NotificationChannelInterface
{
    public function channel(): string
    {
        return NotificationMessage::CHANNEL_SMS;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(string $recipient, array $payload): void
    {
        // Production: POST to the SMS gateway. Stubbed here.
    }
}
