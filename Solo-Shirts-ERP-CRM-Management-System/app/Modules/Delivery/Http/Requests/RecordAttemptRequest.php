<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Requests;

use App\Modules\Delivery\Models\DeliveryAttempt;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class RecordAttemptRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason_code' => ['required', Rule::in(DeliveryAttempt::REASON_CODES)],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
