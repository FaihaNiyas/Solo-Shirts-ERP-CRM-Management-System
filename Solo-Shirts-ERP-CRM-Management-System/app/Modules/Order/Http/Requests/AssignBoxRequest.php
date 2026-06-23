<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class AssignBoxRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', 'in:auto,manual'],
            'box_code' => ['nullable', 'required_if:mode,manual', 'string', 'max:40'],
        ];
    }
}
