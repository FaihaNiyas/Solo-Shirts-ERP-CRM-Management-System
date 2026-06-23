<?php

declare(strict_types=1);

namespace App\Modules\Customer\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class CreateFamilyMemberRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'relation' => ['nullable', 'string', 'max:50'],
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
