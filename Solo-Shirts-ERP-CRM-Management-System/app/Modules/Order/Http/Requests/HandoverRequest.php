<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

/**
 * Front Desk handover payload. Phase 3B-3 processes `pickup`; home_delivery /
 * courier are accepted by the contract but routed to the (future) delivery flow.
 */
final class HandoverRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', 'in:pickup,home_delivery,courier'],
            'otp' => ['nullable', 'string', 'max:12'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
