<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class UpdateItemRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_type' => ['sometimes', 'in:shirt,pant,combo'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'fabric_preference_text' => ['nullable', 'string'],
            'design_notes' => ['nullable', 'array'],
        ];
    }
}
