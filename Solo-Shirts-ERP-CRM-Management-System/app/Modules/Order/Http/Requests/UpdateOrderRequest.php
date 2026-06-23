<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class UpdateOrderRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'source' => ['sometimes', 'in:walk_in,phone,whatsapp,online'],
            'channel_notes' => ['nullable', 'string', 'max:255'],
            'expected_delivery_date' => ['nullable', 'date'],
            'delivery_mode' => ['sometimes', 'in:pickup,home,courier'],
            'delivery_charges_paise' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
