<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Contracts\Validation\Validator;

/**
 * Front Desk order confirmation payload. Pricing + payment are optional — a
 * confirm without them just promotes the lifecycle (Phase 2.5 behaviour). When a
 * total is supplied an invoice is created; when an advance > 0 is supplied a
 * payment is recorded. Amounts are in rupees.
 */
final class ConfirmOrderRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pricing' => ['nullable', 'array'],
            // Phase 3A fallback — a single manual total.
            'pricing.total_amount' => ['nullable', 'numeric', 'min:0'],
            // Phase 3C — per-shirt pricing lines.
            'pricing.lines' => ['nullable', 'array'],
            'pricing.lines.*.order_item_id' => ['required_with:pricing.lines', 'integer'],
            'pricing.lines.*.base_price' => ['required_with:pricing.lines', 'numeric', 'min:0'],
            'pricing.lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'pricing.lines.*.gst_rate' => ['nullable', 'numeric', 'in:0,5,12,18'],

            'payment' => ['nullable', 'array'],
            'payment.advance_amount' => ['nullable', 'numeric', 'min:0'],
            'payment.method' => ['nullable', 'in:cash,upi,bank_transfer'],
            'payment.reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $advance = (float) ($this->input('payment.advance_amount') ?? 0);

            if ($advance > 0 && blank($this->input('payment.method'))) {
                $validator->errors()->add('payment.method', 'Select a payment method to record an advance.');
            }

            $lines = $this->input('pricing.lines');

            if (is_array($lines)) {
                // Per-line: discount cannot exceed the base price (the grand-total
                // vs advance check is enforced server-side after GST is computed).
                foreach ($lines as $i => $line) {
                    if ((float) ($line['discount_amount'] ?? 0) > (float) ($line['base_price'] ?? 0)) {
                        $validator->errors()->add("pricing.lines.{$i}.discount_amount", 'Discount cannot exceed the price.');
                    }
                }
            } else {
                // Single-total path: advance cannot exceed the manual total.
                $total = (float) ($this->input('pricing.total_amount') ?? 0);
                if ($advance > $total) {
                    $validator->errors()->add('payment.advance_amount', 'Advance cannot exceed the order total.');
                }
            }
        });
    }
}
