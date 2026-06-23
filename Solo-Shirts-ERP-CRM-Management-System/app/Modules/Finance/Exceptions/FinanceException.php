<?php

declare(strict_types=1);

namespace App\Modules\Finance\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

final class FinanceException extends DomainException
{
    public function __construct(string $message, string $code, int $status)
    {
        $this->errorCode = $code;
        $this->status = $status;
        parent::__construct($message);
    }

    public static function orderCancelled(): self
    {
        return new self('Cannot invoice a cancelled order.', 'ORDER_CANCELLED', 409);
    }

    public static function paymentExceedsBalance(): self
    {
        return new self('The payment exceeds the invoice outstanding balance.', 'PAYMENT_EXCEEDS_BALANCE', 422);
    }

    public static function creditExceedsInvoice(): self
    {
        return new self('The credit note exceeds the invoice total.', 'CREDIT_EXCEEDS_INVOICE', 422);
    }

    public static function idempotencyKeyRequired(): self
    {
        return new self('An Idempotency-Key header is required to record a payment.', 'IDEMPOTENCY_KEY_REQUIRED', 400);
    }

    public static function pdfNotAvailable(): self
    {
        return new self('No PDF has been generated for this invoice yet.', 'PDF_NOT_AVAILABLE', 404);
    }
}
