<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class CreateDamageReportRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fabric_roll_id' => ['required', 'integer', 'exists:fabric_rolls,id'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'order_item_id' => ['nullable', 'integer', 'exists:order_items,id'],
            'stage' => ['required', Rule::in(['receiving', 'cutting', 'tailoring', 'qc', 'ironing', 'packing'])],
            'damage_type' => ['required', Rule::in(['tear', 'stain', 'color_bleed', 'mis_cut', 'machine_oil', 'other'])],
            'damage_type_other' => [
                'nullable', 'string', 'max:100',
                Rule::requiredIf(fn (): bool => $this->input('damage_type') === 'other'),
            ],
            'quantity_lost_metres' => ['required', 'numeric', 'min:0.01'],
            'action_taken' => ['nullable', 'string', 'max:255'],
            'photo_ids' => ['nullable', 'array'],
            'photo_ids.*' => ['integer', 'exists:damage_report_photos,id'],
        ];
    }
}
