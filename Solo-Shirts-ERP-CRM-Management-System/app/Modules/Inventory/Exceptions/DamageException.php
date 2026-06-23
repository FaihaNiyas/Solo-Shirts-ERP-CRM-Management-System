<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

final class DamageException extends DomainException
{
    public function __construct(string $message, string $code, int $status)
    {
        $this->errorCode = $code;
        $this->status = $status;
        parent::__construct($message);
    }

    public static function invalidRoll(): self
    {
        return new self('The fabric roll is invalid or belongs to another branch.', 'INVALID_ROLL', 422);
    }

    public static function notPending(): self
    {
        return new self('This damage report is no longer pending.', 'NOT_PENDING', 409);
    }

    public static function alreadyApproved(): self
    {
        return new self('This damage report has already been approved.', 'ALREADY_APPROVED', 409);
    }

    public static function alreadyRejected(): self
    {
        return new self('This damage report has already been rejected.', 'ALREADY_REJECTED', 409);
    }

    public static function photoTooLarge(): self
    {
        return new self('The photo exceeds the 5MB limit.', 'PAYLOAD_TOO_LARGE', 413);
    }
}
