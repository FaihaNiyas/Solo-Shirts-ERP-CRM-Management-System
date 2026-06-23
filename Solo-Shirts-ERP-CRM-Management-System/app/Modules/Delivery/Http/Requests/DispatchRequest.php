<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class DispatchRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'channel' => ['nullable', 'string', 'in:sms,whatsapp'],
        ];
    }
}
