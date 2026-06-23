<?php

declare(strict_types=1);

namespace App\Modules\Production\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

final class FabricException extends DomainException
{
    public function __construct(string $message, string $code, int $status)
    {
        $this->errorCode = $code;
        $this->status = $status;
        parent::__construct($message);
    }

    public static function insufficientStock(): self
    {
        return new self('The roll does not have enough available fabric for this reservation.', 'INSUFFICIENT_AVAILABLE_STOCK', 409);
    }

    public static function invalidRoll(): self
    {
        return new self('The fabric roll is invalid or belongs to another branch.', 'INVALID_ROLL', 422);
    }

    public static function rollNotAvailable(): self
    {
        return new self('The fabric roll is not available for reservation.', 'ROLL_NOT_AVAILABLE', 409);
    }

    public static function alreadyAllocated(): self
    {
        return new self('This item already has an active fabric reservation.', 'ALREADY_ALLOCATED', 409);
    }

    public static function noActiveReservation(): self
    {
        return new self('This item has no active fabric reservation.', 'NO_ACTIVE_RESERVATION', 409);
    }

    public static function releaseAfterConsume(): self
    {
        return new self('A consumed reservation can no longer be released.', 'RELEASE_AFTER_CONSUME', 409);
    }

    public static function overConsumeForbidden(): self
    {
        return new self('Consuming more than reserved requires the fabric over-consume permission.', 'OVER_CONSUME_FORBIDDEN', 403);
    }
}
