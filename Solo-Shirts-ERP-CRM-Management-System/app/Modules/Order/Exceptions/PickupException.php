<?php

declare(strict_types=1);

namespace App\Modules\Order\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

/**
 * Pickup-batch domain errors (Phase 2). Mirrors OrderException's code/status
 * envelope so the SPA can branch on a stable `code`.
 */
final class PickupException extends DomainException
{
    public function __construct(string $message, string $code, int $status)
    {
        $this->errorCode = $code;
        $this->status = $status;
        parent::__construct($message);
    }

    public static function noItems(): self
    {
        return new self('Select at least one ready shirt to pick up.', 'PICKUP_NO_ITEMS', 422);
    }

    public static function itemNotInOrder(): self
    {
        return new self('A selected shirt does not belong to this order.', 'PICKUP_ITEM_NOT_IN_ORDER', 422);
    }

    public static function itemNotReady(string $itemCode): self
    {
        return new self("Shirt {$itemCode} is not ready for pickup yet.", 'PICKUP_ITEM_NOT_READY', 422);
    }

    public static function itemAlreadyDelivered(string $itemCode): self
    {
        return new self("Shirt {$itemCode} has already been delivered.", 'PICKUP_ITEM_DELIVERED', 422);
    }

    public static function itemInActiveBatch(string $itemCode): self
    {
        return new self("Shirt {$itemCode} is already in another open pickup batch.", 'PICKUP_ITEM_IN_ACTIVE_BATCH', 409);
    }

    public static function notPayable(): self
    {
        return new self('This pickup batch is not awaiting payment.', 'PICKUP_NOT_PAYABLE', 409);
    }

    public static function paymentExceedsBatchBalance(): self
    {
        return new self('The payment exceeds the pickup batch balance.', 'PICKUP_PAYMENT_EXCEEDS_BALANCE', 422);
    }

    public static function amountNotPositive(): self
    {
        return new self('The payment amount must be greater than zero.', 'PICKUP_AMOUNT_NOT_POSITIVE', 422);
    }

    public static function notPaid(): self
    {
        return new self('Collect the full pickup balance before handover.', 'PICKUP_BALANCE_PENDING', 422);
    }

    public static function alreadyHandedOver(): self
    {
        return new self('This pickup batch has already been handed over.', 'PICKUP_ALREADY_HANDED_OVER', 409);
    }

    public static function cancelled(): self
    {
        return new self('This pickup batch has been cancelled.', 'PICKUP_CANCELLED', 409);
    }

    public static function itemNoLongerReady(string $itemCode): self
    {
        return new self("Shirt {$itemCode} is no longer ready for handover.", 'PICKUP_ITEM_NOT_READY', 409);
    }
}
