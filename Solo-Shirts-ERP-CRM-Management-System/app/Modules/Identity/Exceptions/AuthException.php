<?php

declare(strict_types=1);

namespace App\Modules\Identity\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

/**
 * Authentication / login failures. Carries a per-case code and HTTP status.
 */
final class AuthException extends DomainException
{
    public function __construct(string $message, string $code, int $status)
    {
        $this->errorCode = $code;
        $this->status = $status;
        parent::__construct($message);
    }

    public static function invalidCredentials(): self
    {
        return new self('The email or password is incorrect.', 'INVALID_CREDENTIALS', 401);
    }

    public static function accountInactive(): self
    {
        return new self('Your account is inactive. Please contact administrator.', 'ACCOUNT_INACTIVE', 403);
    }

    public static function branchInactive(): self
    {
        return new self('Your branch is currently inactive. Please contact administrator.', 'BRANCH_INACTIVE', 403);
    }

    public static function lockedOut(): self
    {
        return new self('Too many failed attempts. Try again later.', 'ACCOUNT_LOCKED', 429);
    }

    public static function twoFactorRequired(): self
    {
        return new self('A one-time passcode is required.', 'TWO_FACTOR_REQUIRED', 401);
    }

    public static function invalidOtp(): self
    {
        return new self('The one-time passcode is invalid.', 'INVALID_OTP', 401);
    }

    public static function twoFactorSetupRequired(): self
    {
        return new self('Two-factor authentication must be enabled for this role.', 'TWO_FACTOR_SETUP_REQUIRED', 403);
    }
}
