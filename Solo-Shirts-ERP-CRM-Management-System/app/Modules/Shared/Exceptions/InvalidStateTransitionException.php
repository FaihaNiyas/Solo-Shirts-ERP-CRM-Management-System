<?php

declare(strict_types=1);

namespace App\Modules\Shared\Exceptions;

final class InvalidStateTransitionException extends DomainException
{
    protected string $errorCode = 'INVALID_STATE_TRANSITION';

    protected int $status = 409;

    protected function defaultMessage(): string
    {
        return 'This state transition is not allowed.';
    }
}
