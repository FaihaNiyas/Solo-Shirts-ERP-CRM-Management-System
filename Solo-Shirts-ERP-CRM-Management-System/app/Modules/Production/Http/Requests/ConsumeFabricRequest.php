<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

/**
 * Records the actual metres used when marking a reservation consumed. Omitting
 * actual_metres consumes the full reserved amount; the service caps it at the
 * reserved metres (no over-consumption from the workbench).
 */
final class ConsumeFabricRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'actual_metres' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
