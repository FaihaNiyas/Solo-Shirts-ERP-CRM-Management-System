<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Requests;

use App\Modules\Order\Models\WhatsappNotification;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class SendWhatsappRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_type' => ['required', Rule::in(WhatsappNotification::EVENTS)],
            'order_item_id' => ['nullable', 'integer'],
            'message_body' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
