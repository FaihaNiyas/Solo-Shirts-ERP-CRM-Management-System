<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Modules\Production\Models\QcInspection;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class InspectRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'disposition' => ['required', Rule::in([
                QcInspection::DISPOSITION_PASS,
                QcInspection::DISPOSITION_PASS_WITH_NOTE,
                QcInspection::DISPOSITION_REWORK,
                QcInspection::DISPOSITION_REJECT,
            ])],
            // A rejection must carry a reason.
            'notes' => [
                'nullable',
                'string',
                'max:2000',
                Rule::requiredIf(fn (): bool => $this->input('disposition') === QcInspection::DISPOSITION_REJECT),
            ],
            'defects' => ['nullable', 'array'],
            'defects.*.category_id' => ['required_with:defects', 'integer', 'exists:defect_categories,id'],
            'defects.*.severity' => ['required_with:defects', Rule::in(['minor', 'major', 'critical'])],
            'defects.*.notes' => ['nullable', 'string', 'max:500'],
            'defects.*.photo_ids' => ['nullable', 'array'],
            'defects.*.photo_ids.*' => ['integer', 'exists:qc_defect_photos,id'],
        ];
    }
}
