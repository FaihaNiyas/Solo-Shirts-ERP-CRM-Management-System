<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class RejectVersionRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }
}
