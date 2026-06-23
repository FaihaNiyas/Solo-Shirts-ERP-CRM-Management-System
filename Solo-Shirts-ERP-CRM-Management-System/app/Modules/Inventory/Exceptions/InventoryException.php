<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

final class InventoryException extends DomainException
{
    public function __construct(string $message, string $code, int $status)
    {
        $this->errorCode = $code;
        $this->status = $status;
        parent::__construct($message);
    }

    public static function insufficientStock(): self
    {
        return new self('The roll does not have enough remaining stock for this movement.', 'INSUFFICIENT_STOCK', 409);
    }

    public static function approvalRequired(): self
    {
        return new self('This action requires owner approval.', 'INVENTORY_APPROVAL_REQUIRED', 403);
    }

    public static function rollWrittenOff(): self
    {
        return new self('This roll is written off and can no longer be adjusted.', 'ROLL_WRITTEN_OFF', 409);
    }

    public static function poNotPlaced(): self
    {
        return new self('Only a placed purchase order can be received.', 'PO_NOT_PLACED', 409);
    }

    public static function poNotDraft(): self
    {
        return new self('Only a draft purchase order can be placed.', 'PO_NOT_DRAFT', 409);
    }

    public static function poAlreadyReceived(): self
    {
        return new self('This purchase order has already been received and cannot be cancelled.', 'PO_ALREADY_RECEIVED', 409);
    }

    public static function overReceiptRequiresApproval(): self
    {
        return new self('Receiving more than ordered requires owner approval.', 'OVER_RECEIPT_REQUIRES_APPROVAL', 409);
    }
}
