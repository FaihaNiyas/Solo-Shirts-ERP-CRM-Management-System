<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Shared\Services\NotificationDispatcher;

/**
 * Test double that captures dispatched notifications in memory so tests can read
 * the raw OTP that the real system would only ever send over a channel.
 */
final class FakeNotificationDispatcher extends NotificationDispatcher
{
    /**
     * @var list<array{channel: string, to: string, payload: array<string, mixed>}>
     */
    public array $sent = [];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(string $channel, string $to, array $payload): void
    {
        $this->sent[] = ['channel' => $channel, 'to' => $to, 'payload' => $payload];
    }

    public function lastOtp(): ?string
    {
        $last = end($this->sent);

        if ($last === false) {
            return null;
        }

        $otp = $last['payload']['otp'] ?? null;

        return is_string($otp) ? $otp : null;
    }
}
