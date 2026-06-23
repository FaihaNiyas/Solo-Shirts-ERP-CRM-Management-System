<?php

declare(strict_types=1);

namespace App\Modules\Finance\Database\Factories;

use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\InvoiceLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceLine>
 */
final class InvoiceLineFactory extends Factory
{
    protected $model = InvoiceLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'order_item_id' => null,
            'description' => 'Tailoring charges',
            'hsn_code' => '9988',
            'quantity' => 1,
            'unit_price_paise' => 100000,
            'taxable_paise' => 100000,
            'gst_rate' => 5.00,
            'tax_paise' => 5000,
        ];
    }
}
