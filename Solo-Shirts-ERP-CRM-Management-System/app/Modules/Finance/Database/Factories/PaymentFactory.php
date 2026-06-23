<?php

declare(strict_types=1);

namespace App\Modules\Finance\Database\Factories;

use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Identity\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
final class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'invoice_id' => Invoice::factory(),
            'method' => Payment::METHOD_CASH,
            'amount_paise' => 50000,
            'reference_no' => null,
            'paid_at' => now(),
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
