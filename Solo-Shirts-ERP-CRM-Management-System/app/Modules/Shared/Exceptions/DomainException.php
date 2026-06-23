<?php

declare(strict_types=1);

namespace App\Modules\Shared\Exceptions;

use Exception;

/**
 * Base class for all business-rule violations. Every domain exception carries a
 * stable machine-readable code, an HTTP status, and optional field errors, so
 * the global handler can render the standard error envelope uniformly.
 */
abstract class DomainException extends Exception
{
    protected string $errorCode = 'DOMAIN_ERROR';

    protected int $status = 400;

    /**
     * @var array<string, array<int, string>>
     */
    protected array $errors = [];

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function __construct(string $message = '', array $errors = [])
    {
        parent::__construct($message !== '' ? $message : $this->defaultMessage());
        $this->errors = $errors;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    protected function defaultMessage(): string
    {
        return 'A business rule was violated.';
    }
}
