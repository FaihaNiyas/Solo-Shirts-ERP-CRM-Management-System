<?php

declare(strict_types=1);

namespace App\Modules\Production\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

final class TailoringException extends DomainException
{
    public function __construct(string $message, string $code, int $status)
    {
        $this->errorCode = $code;
        $this->status = $status;
        parent::__construct($message);
    }

    public static function invalidBundle(): self
    {
        return new self('The bundle is invalid or belongs to another branch.', 'INVALID_BUNDLE', 422);
    }

    public static function invalidTailor(): self
    {
        return new self('The tailor is invalid or belongs to another branch.', 'INVALID_TAILOR', 422);
    }

    public static function tailorInactive(): self
    {
        return new self('An inactive tailor cannot be assigned new work.', 'TAILOR_INACTIVE', 422);
    }

    public static function duplicateActiveAssignment(): self
    {
        return new self('This bundle already has an active assignment.', 'DUPLICATE_ACTIVE_ASSIGNMENT', 409);
    }

    public static function alreadyStarted(): self
    {
        return new self('A started assignment can no longer be reassigned.', 'ASSIGNMENT_ALREADY_STARTED', 409);
    }

    public static function invalidState(): self
    {
        return new self('The assignment is not in a valid state for this action.', 'INVALID_ASSIGNMENT_STATE', 409);
    }

    public static function itemCancelled(): self
    {
        return new self('The order item has been cancelled.', 'ITEM_CANCELLED', 409);
    }
}
