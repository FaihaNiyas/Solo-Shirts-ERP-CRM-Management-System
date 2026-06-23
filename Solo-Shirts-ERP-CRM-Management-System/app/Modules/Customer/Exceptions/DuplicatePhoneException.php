<?php

declare(strict_types=1);

namespace App\Modules\Customer\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

final class DuplicatePhoneException extends DomainException
{
    protected string $errorCode = 'DUPLICATE_PHONE';

    protected int $status = 409;

    public static function forCustomer(int $existingCustomerId): self
    {
        return new self(
            'A customer with this phone number already exists.',
            [
                'phone' => ['This phone number is already registered.'],
                'existing_customer_id' => [(string) $existingCustomerId],
            ],
        );
    }
}
