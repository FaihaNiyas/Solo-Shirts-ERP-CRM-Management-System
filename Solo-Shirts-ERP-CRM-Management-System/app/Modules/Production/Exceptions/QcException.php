<?php

declare(strict_types=1);

namespace App\Modules\Production\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

final class QcException extends DomainException
{
    public function __construct(string $message, string $code, int $status)
    {
        $this->errorCode = $code;
        $this->status = $status;
        parent::__construct($message);
    }

    public static function notInQc(): self
    {
        return new self('This item is not currently awaiting QC inspection.', 'NOT_IN_QC', 409);
    }

    public static function reworkLimit(): self
    {
        return new self('The rework limit has been reached; a QC Supervisor override is required.', 'REWORK_LIMIT', 403);
    }

    public static function photoTooLarge(): self
    {
        return new self('The photo exceeds the 5MB limit.', 'PAYLOAD_TOO_LARGE', 413);
    }
}
