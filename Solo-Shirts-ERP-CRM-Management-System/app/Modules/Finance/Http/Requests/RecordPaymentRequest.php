<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Models\Payment;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class RecordPaymentRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'amount_paise' => ['required', 'integer', 'min:1'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'upi_id' => ['nullable', 'string', 'max:100', 'required_if:method,' . Payment::METHOD_UPI],
            'bank_account_last4' => ['nullable', 'string', 'size:4'],
            'paid_at' => ['nullable', 'date'],
        ];
    }
}
