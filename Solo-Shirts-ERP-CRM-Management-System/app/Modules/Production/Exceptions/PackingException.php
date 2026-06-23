<?php

declare(strict_types=1);

namespace App\Modules\Production\Exceptions;

use App\Modules\Shared\Exceptions\DomainException;

final class PackingException extends DomainException
{
    public function __construct(string $message, string $code, int $status)
    {
        $this->errorCode = $code;
        $this->status = $status;
        parent::__construct($message);
    }

    public static function notInPacking(): self
    {
        return new self('This item is not ready for packing — it must pass QC and be in the packing stage first.', 'NOT_IN_PACKING', 409);
    }

    public static function checklistIncomplete(): self
    {
        return new self('Complete every packing checklist item before marking it packed.', 'PACKING_CHECKLIST_INCOMPLETE', 422);
    }
}
