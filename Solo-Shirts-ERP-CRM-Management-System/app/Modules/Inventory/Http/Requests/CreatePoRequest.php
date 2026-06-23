<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class CreatePoRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'notes' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.fabric_type_id' => ['required', 'integer', 'exists:fabric_types,id'],
            'items.*.colour' => ['nullable', 'string', 'max:50'],
            'items.*.quantity_metres' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price_paise' => ['required', 'integer', 'min:0'],
        ];
    }
}
