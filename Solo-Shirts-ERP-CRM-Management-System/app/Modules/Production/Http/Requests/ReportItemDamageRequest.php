<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Cloth damage / waste reported from the production workbench for a specific
 * sub-order. The fabric roll, order and order item are derived from the item's
 * allocation server-side, so the floor only supplies the stage, type and metres.
 */
final class ReportItemDamageRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
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
