<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class CreateFabricRollRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fabric_type_id' => ['required', 'integer', 'exists:fabric_types,id'],
            'received_length_metres' => ['required', 'numeric', 'min:0.01'],
            'colour' => ['nullable', 'string', 'max:50'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'unit_price_paise' => ['nullable', 'integer', 'min:0'],
            'received_date' => ['nullable', 'date'],
            'rack_location' => ['nullable', 'string', 'max:50'],
            'roll_code' => ['nullable', 'string', 'max:50', 'unique:fabric_rolls,roll_code'],
        ];
    }
}
