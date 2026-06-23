<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Requests;

use App\Modules\Order\Models\AlterationRequest;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class CreateAlterationRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'original_order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'issue_type' => ['required', Rule::in(AlterationRequest::ISSUE_TYPES)],
            'issue_description' => ['required', 'string', 'max:1000'],
            'priority' => ['nullable', 'in:normal,urgent'],
            'charge_required' => ['nullable', 'boolean'],
            'estimated_charge' => ['nullable', 'numeric', 'min:0'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
