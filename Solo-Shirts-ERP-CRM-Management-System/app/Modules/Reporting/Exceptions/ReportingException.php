<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

final class ReportingException extends DomainException
{
    public function __construct(string $message, string $code, int $status)
    {
        $this->errorCode = $code;
        $this->status = $status;
        parent::__construct($message);
    }

    public static function unknownReport(string $kind): self
    {
        return new self("Unknown report kind [{$kind}].", 'UNKNOWN_REPORT', 422);
    }

    public static function notReady(): self
    {
        return new self('The report is not ready for download yet.', 'REPORT_NOT_READY', 409);
    }
}
