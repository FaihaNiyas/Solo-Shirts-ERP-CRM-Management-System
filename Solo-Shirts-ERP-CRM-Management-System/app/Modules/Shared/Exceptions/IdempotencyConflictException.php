<?php

declare(strict_types=1);

namespace App\Modules\Shared\Exceptions;

final class IdempotencyConflictException extends DomainException
{
    protected string $errorCode = 'IDEMPOTENCY_CONFLICT';

    protected int $status = 409;

    public function __construct(string $message = '', string $code = 'IDEMPOTENCY_CONFLICT')
    {
        $this->errorCode = $code;
        parent::__construct($message);
    }

    /**
     * The original request is still being processed (claimed but not yet
     * completed) — the caller should retry.
     */
    public static function inFlight(): self
    {
        return new self('A request with this Idempotency-Key is already being processed.', 'IDEMPOTENCY_IN_FLIGHT');
    }

    protected function defaultMessage(): string
    {
        return 'This Idempotency-Key was already used with a different request.';
    }
}
