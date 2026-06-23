<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base API Resource. Standardizes date formatting to ISO-8601 (with timezone)
 * so every resource serializes timestamps identically.
 */
abstract class BaseResource extends JsonResource
{
    protected function date(?DateTimeInterface $value): ?string
    {
        return $value?->format(DateTimeInterface::ATOM);
    }
}
