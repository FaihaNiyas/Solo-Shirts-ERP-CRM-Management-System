<?php

declare(strict_types=1);

namespace App\Modules\Shared\Exceptions;

final class BranchIsolationException extends DomainException
{
    protected string $errorCode = 'BRANCH_ISOLATION_VIOLATION';

    protected int $status = 403;

    protected function defaultMessage(): string
    {
        return 'This resource belongs to another branch.';
    }
}
