<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Invoice;

/**
 * Computes the GST breakdown for a set of invoice lines. For a regular dealer
 * the tax splits into CGST+SGST on an intra-state supply, or a single IGST on an
 * inter-state supply. Composition and unregistered treatments charge no GST to
 * the customer, so every tax bucket is zero. All money is in integer paise.
 */
final class GstCalculator
{
    /**
     * @param  list<array{taxable_paise: int, gst_rate: float}>  $lines
     * @return array{
     *     subtotal_paise: int,
     *     cgst_paise: int,
     *     sgst_paise: int,
     *     igst_paise: int,
     *     tax_paise: int,
     *     lines: list<array{taxable_paise: int, gst_rate: float, tax_paise: int}>
     * }
     */
    public function calculate(array $lines, string $treatment, bool $interState): array
    {
        $charged = $treatment === Invoice::TREATMENT_REGULAR;

        $subtotal = 0;
        $totalTax = 0;
        $computed = [];

        foreach ($lines as $line) {
            $taxable = $line['taxable_paise'];
            $rate = $line['gst_rate'];
            $lineTax = $charged ? (int) round($taxable * $rate / 100) : 0;

            $subtotal += $taxable;
            $totalTax += $lineTax;

            $computed[] = [
                'taxable_paise' => $taxable,
                'gst_rate' => $rate,
                'tax_paise' => $lineTax,
            ];
        }

        $cgst = 0;
        $sgst = 0;
        $igst = 0;

        if ($totalTax > 0) {
            if ($interState) {
                $igst = $totalTax;
            } else {
                // Split evenly; any odd paise lands on SGST so the two halves
                // always sum back to the line tax.
                $cgst = intdiv($totalTax, 2);
                $sgst = $totalTax - $cgst;
            }
        }

        return [
            'subtotal_paise' => $subtotal,
            'cgst_paise' => $cgst,
            'sgst_paise' => $sgst,
            'igst_paise' => $igst,
            'tax_paise' => $totalTax,
            'lines' => $computed,
        ];
    }
}
