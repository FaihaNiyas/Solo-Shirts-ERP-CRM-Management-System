<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class IssueCreditNoteRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
            'total' => ['required', 'integer', 'min:1'],
        ];
    }
}
