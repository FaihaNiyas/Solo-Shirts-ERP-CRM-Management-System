<?php

declare(strict_types=1);

namespace App\Modules\Customer\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class UpdateCustomerRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'min:7', 'max:20'],
            'address' => ['nullable', 'string'],
            'preferred_fabric_id' => ['nullable', 'integer'],
            'special_notes' => ['nullable', 'string'],
        ];
    }
}
