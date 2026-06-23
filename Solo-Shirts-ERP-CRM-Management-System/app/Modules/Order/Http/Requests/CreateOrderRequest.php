<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Requests;

use App\Modules\Measurement\Rules\UsableMeasurementVersion;
use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class CreateOrderRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $customerId = (int) $this->input('customer_id');

        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'source' => ['required', 'in:walk_in,phone,whatsapp,online'],
            // Intake wizard sends intake_preparation; everything else defaults to
            // order_received (production-visible) for backwards compatibility.
            'lifecycle_status' => ['nullable', 'in:intake_preparation,order_received'],
            'channel_notes' => ['nullable', 'string', 'max:255'],
            'expected_delivery_date' => ['nullable', 'date'],
            'delivery_mode' => ['required', 'in:pickup,home,courier'],
            'delivery_charges_paise' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['present', 'array'],
            'items.*.product_type' => ['required', 'in:shirt,pant,combo'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.measurement_version_id' => ['required', 'integer', new UsableMeasurementVersion($customerId)],
            'items.*.fabric_preference_text' => ['nullable', 'string'],
            'items.*.design_notes' => ['nullable', 'array'],
        ];
    }
}
