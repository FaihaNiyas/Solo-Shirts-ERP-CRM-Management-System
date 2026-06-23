<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class AssignSlotRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Omit slot_code to auto-pick the first available slot.
        return [
            'slot_code' => ['nullable', 'string', 'max:50'],
        ];
    }
}
