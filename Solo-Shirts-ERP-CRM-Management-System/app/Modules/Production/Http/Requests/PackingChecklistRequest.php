<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

/**
 * Phase 7D — save/patch the packing checklist. Each box is optional so the floor
 * can tick them progressively; the boxes are only enforced (all required) at
 * mark-packed time, not here.
 */
final class PackingChecklistRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'checked_measurement_card' => ['sometimes', 'boolean'],
            'checked_buttons' => ['sometimes', 'boolean'],
            'checked_ironing' => ['sometimes', 'boolean'],
            'checked_folded' => ['sometimes', 'boolean'],
            'checked_packing_cover' => ['sometimes', 'boolean'],
            'checked_label' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
