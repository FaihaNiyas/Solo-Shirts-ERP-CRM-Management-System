<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Requests;

use App\Modules\Measurement\Rules\UsableMeasurementVersion;
use App\Modules\Order\Models\Order;
use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class AddItemRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Order|null $order */
        $order = $this->route('order');
        $customerId = $order?->customer_id;

        return [
            'product_type' => ['required', 'in:shirt,pant,combo'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'measurement_version_id' => ['required', 'integer', new UsableMeasurementVersion($customerId)],
            'fabric_preference_text' => ['nullable', 'string'],
            'design_notes' => ['nullable', 'array'],
        ];
    }
}
