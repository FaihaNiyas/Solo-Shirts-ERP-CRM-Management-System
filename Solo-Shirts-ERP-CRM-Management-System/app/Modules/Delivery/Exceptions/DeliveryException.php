<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

final class DeliveryException extends DomainException
{
    public function __construct(string $message, string $code, int $status)
    {
        $this->errorCode = $code;
        $this->status = $status;
        parent::__construct($message);
    }

    public static function notDispatched(): self
    {
        return new self('The delivery has not been dispatched yet.', 'NOT_DISPATCHED', 409);
    }

    public static function alreadyCompleted(): self
    {
        return new self('The delivery is already completed.', 'DELIVERY_ALREADY_COMPLETED', 409);
    }

    public static function cancelled(): self
    {
        return new self('The delivery has been cancelled.', 'DELIVERY_CANCELLED', 409);
    }

    public static function alreadyDispatched(): self
    {
        return new self('The delivery has already been dispatched.', 'ALREADY_DISPATCHED', 409);
    }

    public static function invalidOtp(): self
    {
        return new self('The confirmation code is incorrect.', 'OTP_INVALID', 422);
    }

    public static function expiredOtp(): self
    {
        return new self('The confirmation code has expired; re-dispatch to issue a new one.', 'OTP_EXPIRED', 422);
    }

    public static function lockedOtp(): self
    {
        return new self('Too many incorrect attempts; re-dispatch to issue a new code.', 'OTP_LOCKED', 423);
    }

    public static function balancePending(int $paise): self
    {
        $rupees = number_format($paise / 100, 2);

        return new self("Balance ₹{$rupees} pending. Collect payment before delivery.", 'BALANCE_PENDING', 422);
    }
}
