<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Services;

use App\Modules\Delivery\Exceptions\DeliveryException;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Models\DeliveryOtp;
use Illuminate\Support\Facades\Hash;

/**
 * Issues and verifies one-time delivery confirmation codes. Only the bcrypt
 * hash is ever persisted; the raw code is returned once to the caller (which
 * hands it to the notification channel) and never stored. Verification is
 * constant-time (Hash::check), expires after TTL_MINUTES, and locks the code
 * after MAX_ATTEMPTS failures — after which a fresh dispatch is required.
 */
final class OtpService
{
    public const LENGTH = 6;

    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /**
     * Generate, hash and persist a fresh OTP for the delivery. Returns the raw
     * 6-digit code so the caller can transmit it via the notification channel.
     */
    public function issue(Delivery $delivery): string
    {
        $raw = $this->generate();

        DeliveryOtp::query()->create([
            'delivery_id' => $delivery->id,
            'otp_hash' => Hash::make($raw),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'attempts' => 0,
            'used_at' => null,
        ]);

        return $raw;
    }

    /**
     * Verify a candidate code against the delivery's most recent OTP. Throws on
     * a missing/expired/locked code or a wrong value; marks the code used on a
     * correct match. A wrong attempt increments the counter, and the attempt
     * that reaches MAX_ATTEMPTS locks the code (423).
     */
    public function verify(Delivery $delivery, string $candidate): void
    {
        /** @var DeliveryOtp|null $otp */
        $otp = DeliveryOtp::query()
            ->where('delivery_id', $delivery->id)
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if ($otp === null || $otp->isUsed()) {
            throw DeliveryException::notDispatched();
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            throw DeliveryException::lockedOtp();
        }

        if ($otp->isExpired()) {
            throw DeliveryException::expiredOtp();
        }

        if (!Hash::check($candidate, $otp->otp_hash)) {
            $otp->increment('attempts');

            throw $otp->attempts >= self::MAX_ATTEMPTS
                ? DeliveryException::lockedOtp()
                : DeliveryException::invalidOtp();
        }

        $otp->update(['used_at' => now()]);
    }

    public function generate(): string
    {
        return str_pad((string) random_int(0, 999999), self::LENGTH, '0', STR_PAD_LEFT);
    }
}
