<?php

declare(strict_types=1);

namespace App\Modules\Shared\Exceptions;

final class InsufficientStockException extends DomainException
{
    protected string $errorCode = 'INSUFFICIENT_STOCK';

    protected int $status = 409;

    protected function defaultMessage(): string
    {
        return 'Insufficient stock available for this operation.';
    }
}
