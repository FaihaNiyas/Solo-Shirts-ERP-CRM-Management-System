<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Requests;

/**
 * Minimal Form Request that exercises BaseFormRequest's standardized validation
 * envelope from a smoke endpoint.
 */
final class SmokeFormRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
        ];
    }
}
