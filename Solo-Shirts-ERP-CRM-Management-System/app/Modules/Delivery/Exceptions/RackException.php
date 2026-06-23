<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

final class RackException extends DomainException
{
    public function __construct(string $message, string $code, int $status)
    {
        $this->errorCode = $code;
        $this->status = $status;
        parent::__construct($message);
    }

    public static function slotNotFound(): self
    {
        return new self('The rack slot does not exist in this branch.', 'RACK_SLOT_NOT_FOUND', 404);
    }

    public static function slotOccupied(): self
    {
        return new self('The rack slot is already occupied.', 'RACK_SLOT_OCCUPIED', 409);
    }

    public static function slotInactive(): self
    {
        return new self('The rack slot is inactive.', 'RACK_SLOT_INACTIVE', 409);
    }

    public static function itemAlreadyAssigned(): self
    {
        return new self('This item already occupies a rack slot.', 'ITEM_ALREADY_ASSIGNED', 409);
    }

    public static function noSlotAvailable(): self
    {
        return new self('No rack slot is available; free a slot first.', 'NO_SLOT_AVAILABLE', 409);
    }

    public static function notAssigned(): self
    {
        return new self('This item is not currently assigned to a rack slot.', 'ITEM_NOT_ASSIGNED', 409);
    }
}
