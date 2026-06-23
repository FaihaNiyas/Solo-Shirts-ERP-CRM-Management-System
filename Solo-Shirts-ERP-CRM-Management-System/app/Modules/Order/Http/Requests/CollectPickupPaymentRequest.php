<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Requests;

use App\Modules\Finance\Models\Payment;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Pickup-batch payment payload. Amount in rupees; method as the shared payment
 * methods. Narrow by design — the batch scopes which shirts the money applies to.
 */
final class CollectPickupPaymentRequest extends BaseFormRequest
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
        ];
    }
}
