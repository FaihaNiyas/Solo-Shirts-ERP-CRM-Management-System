<?php

declare(strict_types=1);

namespace App\Modules\Order\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

final class OrderException extends DomainException
{
    public function __construct(string $message, string $code, int $status)
    {
        $this->errorCode = $code;
        $this->status = $status;
        parent::__construct($message);
    }

    public static function invalidCustomer(): self
    {
        return new self('The customer is invalid or no longer available.', 'INVALID_CUSTOMER', 422);
    }

    public static function requiresItem(): self
    {
        return new self('An order must contain at least one item.', 'ORDER_REQUIRES_ITEM', 422);
    }

    public static function invalidStateForEdit(): self
    {
        return new self('This item can no longer be edited at its current stage.', 'INVALID_STATE_FOR_EDIT', 409);
    }

    public static function invalidStateForCancel(): self
    {
        return new self('This order can no longer be cancelled at its current stage.', 'INVALID_STATE_FOR_CANCEL', 409);
    }

    public static function boxCodeRequired(): self
    {
        return new self('A box code is required for manual assignment.', 'BOX_CODE_REQUIRED', 422);
    }

    public static function boxOccupied(string $boxCode, ?string $itemCode): self
    {
        $by = $itemCode !== null ? " by {$itemCode}" : '';

        return new self("{$boxCode} is already used{$by}.", 'BOX_OCCUPIED', 409);
    }

    public static function boxNotAssigned(): self
    {
        return new self('Assign a production box before marking it placed.', 'BOX_NOT_ASSIGNED', 422);
    }

    public static function confirmMissingBox(string $itemCode): self
    {
        return new self("Sub-order {$itemCode} has no production box assigned.", 'CONFIRM_MISSING_BOX', 422);
    }

    public static function confirmMissingPdf(string $itemCode): self
    {
        return new self("Sub-order {$itemCode} has no generated job-card PDF.", 'CONFIRM_MISSING_PDF', 422);
    }

    public static function cannotConfirmCancelled(): self
    {
        return new self('A cancelled order cannot be confirmed.', 'ORDER_CANCELLED', 409);
    }

    public static function notConfirmedForProduction(): self
    {
        return new self('This order is still in intake preparation and has not been confirmed for production.', 'ORDER_NOT_CONFIRMED', 409);
    }

    public static function noInvoice(): self
    {
        return new self('This order has no invoice yet, so a payment cannot be recorded.', 'NO_INVOICE', 422);
    }

    public static function paymentExceedsBalance(): self
    {
        return new self('The payment amount exceeds the outstanding balance.', 'PAYMENT_EXCEEDS_BALANCE', 422);
    }

    public static function orderNotReady(): self
    {
        return new self('The order has no sub-orders ready for pickup yet.', 'ORDER_NOT_READY', 409);
    }

    public static function balancePending(int $paise): self
    {
        $rupees = number_format($paise / 100, 0);

        return new self("Balance ₹{$rupees} pending. Collect payment before handover.", 'BALANCE_PENDING', 422);
    }

    public static function handoverModeUnsupported(): self
    {
        return new self('Only pickup handover is supported at the front desk.', 'HANDOVER_MODE_UNSUPPORTED', 422);
    }

    public static function discountExceedsLine(string $itemCode): self
    {
        return new self("Discount for {$itemCode} exceeds its price and charges.", 'DISCOUNT_EXCEEDS_LINE', 422);
    }

    public static function pricingLineMismatch(): self
    {
        return new self('A pricing line does not match an active sub-order, or a sub-order is priced twice.', 'PRICING_LINE_MISMATCH', 422);
    }

    public static function pricingLinesIncomplete(): self
    {
        return new self('Every sub-order must have exactly one pricing line.', 'PRICING_LINES_INCOMPLETE', 422);
    }

    public static function missingPhoneForNotification(): self
    {
        return new self('The customer has no valid phone number for WhatsApp.', 'MISSING_PHONE', 422);
    }

    public static function alterationItemNotFound(): self
    {
        return new self('The sub-order was not found in this branch.', 'ALTERATION_ITEM_NOT_FOUND', 404);
    }

    public static function itemNotDeliveredForAlteration(): self
    {
        return new self('An alteration can only be created after the shirt has been delivered.', 'ITEM_NOT_DELIVERED', 422);
    }

    public static function invalidAlterationTransition(string $from, string $to): self
    {
        return new self("Cannot move alteration from {$from} to {$to}.", 'INVALID_ALTERATION_TRANSITION', 422);
    }

    public static function measurementRequiredForProduction(): self
    {
        return new self('This item has no measurement and cannot move through production.', 'MEASUREMENT_REQUIRED', 422);
    }
}
