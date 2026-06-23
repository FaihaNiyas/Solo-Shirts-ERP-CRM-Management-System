<?php

declare(strict_types=1);

namespace App\Modules\Printing\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

final class PrintingException extends DomainException
{
    public function __construct(string $message, string $code, int $status)
    {
        $this->errorCode = $code;
        $this->status = $status;
        parent::__construct($message);
    }

    public static function storageUnavailable(): self
    {
        return new self('The document storage backend is unavailable.', 'STORAGE_UNAVAILABLE', 503);
    }

    public static function unknownKind(string $kind): self
    {
        return new self("Unknown document kind [{$kind}].", 'UNKNOWN_DOCUMENT_KIND', 422);
    }

    public static function referenceNotFound(): self
    {
        return new self('The referenced record for this document was not found.', 'DOCUMENT_REFERENCE_NOT_FOUND', 404);
    }
}
