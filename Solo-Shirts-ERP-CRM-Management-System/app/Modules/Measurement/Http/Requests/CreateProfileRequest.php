<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Http\Requests;

use App\Modules\Measurement\Http\Requests\Concerns\ValidatesMeasurementData;
use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class CreateProfileRequest extends BaseFormRequest
{
    use ValidatesMeasurementData;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:shirt,pant,both'],
            'family_member_id' => ['nullable', 'integer', 'exists:family_members,id'],
            'is_default' => ['nullable', 'boolean'],
        ], $this->measurementRules());
    }
}
