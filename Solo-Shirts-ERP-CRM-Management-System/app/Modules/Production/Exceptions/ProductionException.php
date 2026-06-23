<?php

declare(strict_types=1);

namespace App\Modules\Production\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

final class ProductionException extends DomainException
{
    public function __construct(string $message, string $code, int $status)
    {
        $this->errorCode = $code;
        $this->status = $status;
        parent::__construct($message);
    }

    public static function invalidTransition(string $from, string $to): self
    {
        return new self(
            "An item in '{$from}' cannot transition to '{$to}'.",
            'INVALID_STATE_TRANSITION',
            409,
        );
    }

    public static function reworkLimitExceeded(): self
    {
        return new self(
            'This item has reached the rework limit; a QC Supervisor override is required.',
            'REWORK_LIMIT_EXCEEDED',
            409,
        );
    }

    public static function alreadyOnHold(): self
    {
        return new self('This item is already on hold.', 'ALREADY_ON_HOLD', 409);
    }

    public static function notOnHold(): self
    {
        return new self('This item is not on hold.', 'NOT_ON_HOLD', 409);
    }

    public static function cannotHoldTerminal(): self
    {
        return new self(
            'A delivered or cancelled item cannot be put on hold.',
            'CANNOT_HOLD_TERMINAL',
            409,
        );
    }

    public static function issueAlreadyResolved(): self
    {
        return new self('This issue is already resolved.', 'ISSUE_ALREADY_RESOLVED', 409);
    }
}
