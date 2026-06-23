<?php

declare(strict_types=1);

namespace App\Modules\Shared\Services;

use Illuminate\Support\Facades\Log;

/**
 * Default NotificationDispatcher: records the intent to the application log
 * without exposing secrets. Sensitive payload values (e.g. an OTP) are never
 * logged in clear — only the set of keys is recorded. Phase 17 replaces this
 * with real SMS/WhatsApp gateways.
 */
final class LogNotificationDispatcher extends NotificationDispatcher
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(string $channel, string $to, array $payload): void
    {
        Log::info('notification.dispatch', [
            'channel' => $channel,
            'to' => $to,
            'payload_keys' => array_keys($payload),
        ]);
    }
}
