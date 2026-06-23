<?php

declare(strict_types=1);

namespace App\Modules\Shared\Exceptions;

final class ApprovalRequiredException extends DomainException
{
    protected string $errorCode = 'APPROVAL_REQUIRED';

    protected int $status = 403;

    protected function defaultMessage(): string
    {
        return 'This action requires approval.';
    }
}
