<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class CompleteCuttingRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'actual_metres' => ['required', 'numeric', 'min:0.01'],
            'bundles' => ['required', 'array', 'min:1'],
            'bundles.*.pieces' => ['required', 'integer', 'min:1'],
            'bundles.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
