<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class ReceivePoRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_item_id' => ['required', 'integer', 'exists:purchase_order_items,id'],
            'lines.*.metres' => ['required', 'numeric', 'min:0.01'],
            'lines.*.rack_location' => ['nullable', 'string', 'max:50'],
        ];
    }
}
