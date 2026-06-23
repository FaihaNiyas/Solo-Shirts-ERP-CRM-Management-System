<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class UpdateProfileRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
