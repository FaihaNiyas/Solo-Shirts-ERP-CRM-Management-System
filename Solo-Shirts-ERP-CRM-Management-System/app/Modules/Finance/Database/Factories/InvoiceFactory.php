<?php

declare(strict_types=1);

namespace App\Modules\Finance\Database\Factories;

use App\Modules\Customer\Models\Customer;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Identity\Models\Branch;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
final class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'invoice_no' => 'INV-' . strtoupper(Str::random(10)),
            'order_id' => Order::factory(),
            'customer_id' => Customer::factory(),
            'gst_treatment' => Invoice::TREATMENT_REGULAR,
            'subtotal_paise' => 100000,
            'cgst_paise' => 0,
            'sgst_paise' => 0,
            'igst_paise' => 0,
            'delivery_charges_paise' => 0,
            'discount_paise' => 0,
            'total_paise' => 100000,
            'issued_at' => now(),
            'status' => Invoice::STATUS_ISSUED,
        ];
    }
}
