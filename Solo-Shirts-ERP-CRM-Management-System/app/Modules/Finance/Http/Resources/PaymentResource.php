<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Resources;

use App\Modules\Finance\Models\Payment;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * The UPI ID is intentionally never serialized; only the bank account last-4 is
 * surfaced.
 *
 * @mixin Payment
 */
final class PaymentResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'method' => $this->method,
            'amount_paise' => $this->amount_paise,
            'reference_no' => $this->reference_no,
            'bank_account_last4' => $this->bank_account_last4,
            'paid_at' => $this->date($this->paid_at),
            'recorded_by' => $this->recorded_by,
        ];
    }
}
