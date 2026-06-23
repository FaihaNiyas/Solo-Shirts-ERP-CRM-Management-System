<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Requests;

use App\Modules\Finance\Models\Payment;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Front Desk balance-collection payload for an order's invoice. Amount is in
 * rupees. Narrow by design: no invoice_id (the order scopes it), no GST fields.
 */
final class RecordOrderPaymentRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
