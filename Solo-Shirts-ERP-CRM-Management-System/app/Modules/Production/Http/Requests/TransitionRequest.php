<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Modules\Order\Models\OrderItem;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class TransitionRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // The completed/rejected piece counts are bounded by the item's quantity
        // (route-model-bound). Falls back to a generous cap if the item is missing.
        $item = $this->route('item');
        $max = $item instanceof OrderItem ? max(1, (int) $item->quantity) : 100000;

        return [
            'to' => ['required', 'string', Rule::in(OrderItem::WORKFLOW_STATES)],
            // A reason is mandatory when sending an item back for rework or
            // cancelling it, so the audit trail always explains the why.
            'notes' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf(fn (): bool => in_array(
                    $this->input('to'),
                    [OrderItem::STATE_REWORK, OrderItem::STATE_CANCELLED],
                    true,
                )),
            ],
            // "Complete stage" confirmation form (Kanban). Advisory ledger metadata.
            'completed_qty' => ['nullable', 'integer', 'min:0', "max:{$max}"],
            'rejected_qty' => ['nullable', 'integer', 'min:0', "max:{$max}"],
            'attachment_path' => ['nullable', 'string', 'max:2048'],
            // Pickup box / shelf number captured when staging for delivery.
            'delivery_box_code' => ['nullable', 'string', 'max:50'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
