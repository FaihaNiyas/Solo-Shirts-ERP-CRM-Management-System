<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Models\Invoice;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class CreateInvoiceRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'gst_treatment' => ['required', Rule::in(Invoice::TREATMENTS)],
            'inter_state' => ['sometimes', 'boolean'],
            'delivery_charges_paise' => ['nullable', 'integer', 'min:0'],
            'discount_paise' => ['nullable', 'integer', 'min:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.order_item_id' => ['nullable', 'integer', 'exists:order_items,id'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.hsn_code' => ['nullable', 'string', 'max:20'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_price_paise' => ['required', 'integer', 'min:0'],
            'lines.*.gst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
