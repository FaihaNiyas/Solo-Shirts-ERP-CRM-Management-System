<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Notifications;

/**
 * A delivery channel for outbound notifications (WhatsApp, email, SMS). Implementations
 * throw NotificationRateLimitedException when the provider quota is exhausted so the
 * caller can keep the message queued for retry rather than dropping it.
 */
interface NotificationChannelInterface
{
    public function channel(): string;

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws NotificationRateLimitedException
     */
    public function send(string $recipient, array $payload): void;
}
