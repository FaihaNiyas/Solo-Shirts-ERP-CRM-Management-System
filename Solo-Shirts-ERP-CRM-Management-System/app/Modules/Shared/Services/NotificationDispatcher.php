<?php

declare(strict_types=1);

namespace App\Modules\Shared\Services;

/**
 * Channel-agnostic outbound notification seam. Concrete senders (SMS, WhatsApp,
 * email) are wired in Phase 17; for now the default binding logs the intent. The
 * abstraction exists so domain services (e.g. delivery OTP dispatch) depend on a
 * stable contract rather than a specific gateway.
 */
abstract class NotificationDispatcher
{
    /**
     * @param  array<string, mixed>  $payload
     */
    abstract public function send(string $channel, string $to, array $payload): void;
}
