<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class AllocateFabricRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'roll_id' => ['required', 'integer', 'exists:fabric_rolls,id'],
            'metres' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
