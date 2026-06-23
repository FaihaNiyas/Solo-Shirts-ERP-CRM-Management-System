<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Requests;

use App\Modules\Delivery\Models\Delivery;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class CreateDeliveryRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'mode' => ['required', Rule::in(Delivery::MODES)],
            'address_snapshot' => ['nullable'],
            'courier_partner' => ['nullable', 'string', 'max:100', 'required_if:mode,' . Delivery::MODE_COURIER],
            'tracking_no' => ['nullable', 'string', 'max:100'],
            'scheduled_at' => ['nullable', 'date'],
            'delivery_charges_paise' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
