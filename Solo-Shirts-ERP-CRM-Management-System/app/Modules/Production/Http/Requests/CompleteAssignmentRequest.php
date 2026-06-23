<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class CompleteAssignmentRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
