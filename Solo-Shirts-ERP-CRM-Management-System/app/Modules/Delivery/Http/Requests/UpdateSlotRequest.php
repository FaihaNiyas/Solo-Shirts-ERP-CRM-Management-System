<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class UpdateSlotRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
