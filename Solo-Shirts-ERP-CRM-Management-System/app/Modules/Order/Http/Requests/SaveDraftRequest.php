<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Closure;

final class SaveDraftRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer'],
            'family_member_id' => ['nullable', 'integer'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'title' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,paused'],
            'current_step' => ['nullable', 'string', 'max:30'],
            'completed_count' => ['nullable', 'integer', 'min:0'],
            'total_items' => ['nullable', 'integer', 'min:0'],
            'draft_payload' => [
                'nullable',
                'array',
                // Keep payloads sane — a wizard draft should never approach 512 KB.
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (strlen((string) json_encode($value)) > 524288) {
                        $fail('The draft payload is too large.');
                    }
                },
            ],
        ];
    }
}
