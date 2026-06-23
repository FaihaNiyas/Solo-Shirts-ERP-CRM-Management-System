<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

final class TwoFactorService
{
    public function __construct(private readonly Google2FA $google2fa) {}

    /**
     * Generate and persist a (still unconfirmed) secret, returning the secret
     * plus an otpauth:// URL the client renders as a QR code.
     *
     * @return array{secret: string, otpauth_url: string}
     */
    public function enable(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = null;
        $user->save();

        $issuer = (string) config('app.name', 'Solo Shirts ERP');

        return [
            'secret' => $secret,
            'otpauth_url' => $this->google2fa->getQRCodeUrl($issuer, $user->email, $secret),
        ];
    }

    public function confirm(User $user, string $otp): bool
    {
        if (!$this->verify($user, $otp)) {
            return false;
        }

        $user->two_factor_confirmed_at = now();
        $user->save();

        return true;
    }

    public function disable(User $user): void
    {
        $user->two_factor_secret = null;
        $user->two_factor_confirmed_at = null;
        $user->save();
    }

    public function verify(User $user, string $otp): bool
    {
        $secret = $user->two_factor_secret;

        return $secret !== null && $this->google2fa->verifyKey($secret, $otp);
    }
}
