<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class HoldItemRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // A reason is mandatory so the board always explains why work paused.
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
