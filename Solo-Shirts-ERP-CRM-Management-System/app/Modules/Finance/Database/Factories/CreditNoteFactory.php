<?php

declare(strict_types=1);

namespace App\Modules\Finance\Database\Factories;

use App\Modules\Finance\Models\CreditNote;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Identity\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CreditNote>
 */
final class CreditNoteFactory extends Factory
{
    protected $model = CreditNote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'credit_no' => 'CN-' . strtoupper(Str::random(10)),
            'invoice_id' => Invoice::factory(),
            'reason' => 'Billing correction',
            'total_paise' => 10000,
            'issued_at' => now(),
        ];
    }
}
