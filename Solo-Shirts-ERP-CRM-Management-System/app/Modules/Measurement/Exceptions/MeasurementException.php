<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

final class MeasurementException extends DomainException
{
    public function __construct(string $message, string $code, int $status)
    {
        $this->errorCode = $code;
        $this->status = $status;
        parent::__construct($message);
    }

    public static function alreadyApproved(): self
    {
        return new self('This measurement version is already approved.', 'ALREADY_APPROVED', 409);
    }

    public static function notPending(): self
    {
        return new self('Only a pending version can be approved.', 'INVALID_STATE_TRANSITION', 409);
    }

    public static function cannotRejectApproved(): self
    {
        return new self('An approved version cannot be rejected; create a new version instead.', 'CANNOT_REJECT_APPROVED', 409);
    }

    public static function invalidData(string $message): self
    {
        return new self($message, 'INVALID_MEASUREMENT_DATA', 422);
    }
}
