<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Notifications;

use App\Modules\Reporting\Models\NotificationMessage;

/**
 * SMTP email channel. The actual Mailable dispatch is wired in deployment; here
 * the gateway call is a stub that always succeeds.
 */
final class EmailChannel implements NotificationChannelInterface
{
    public function channel(): string
    {
        return NotificationMessage::CHANNEL_EMAIL;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(string $recipient, array $payload): void
    {
        // Production: dispatch a Mailable over SMTP. Stubbed here.
    }
}
