<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Notifications;

use App\Modules\Reporting\Models\NotificationMessage;
use Illuminate\Support\Facades\RateLimiter;

/**
 * WhatsApp Business API channel. Sends are capped to the provider's per-minute
 * quota via a rate limiter; when the quota is exhausted the send is refused with
 * NotificationRateLimitedException so the message can be retried later, not lost.
 * The actual Business API HTTP call is wired in deployment (template messages
 * only); here the gateway call is a stub.
 */
final class WhatsappChannel implements NotificationChannelInterface
{
    private const RATE_KEY = 'notifications:whatsapp';

    public function channel(): string
    {
        return NotificationMessage::CHANNEL_WHATSAPP;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(string $recipient, array $payload): void
    {
        $allowed = RateLimiter::attempt(
            self::RATE_KEY,
            $this->perMinute(),
            static function (): void {
                // Production: POST to the WhatsApp Business API using a pre-approved
                // template id. Stubbed here.
            },
            60,
        );

        if ($allowed === false) {
            throw new NotificationRateLimitedException('WhatsApp per-minute quota exhausted.');
        }
    }

    private function perMinute(): int
    {
        $limit = config('notifications.whatsapp_per_minute', 60);

        return is_numeric($limit) ? (int) $limit : 60;
    }
}
