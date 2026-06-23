<?php

declare(strict_types=1);

use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Services\GstCalculator;

it('splits regular GST into CGST+SGST for an intra-state supply', function () {
    $calc = new GstCalculator;

    $result = $calc->calculate(
        [['taxable_paise' => 100000, 'gst_rate' => 5.0]],
        Invoice::TREATMENT_REGULAR,
        interState: false,
    );

    expect($result['subtotal_paise'])->toBe(100000)
        ->and($result['tax_paise'])->toBe(5000)
        ->and($result['cgst_paise'])->toBe(2500)
        ->and($result['sgst_paise'])->toBe(2500)
        ->and($result['igst_paise'])->toBe(0);
});

it('charges a single IGST for an inter-state supply', function () {
    $calc = new GstCalculator;

    $result = $calc->calculate(
        [['taxable_paise' => 100000, 'gst_rate' => 5.0]],
        Invoice::TREATMENT_REGULAR,
        interState: true,
    );

    expect($result['igst_paise'])->toBe(5000)
        ->and($result['cgst_paise'])->toBe(0)
        ->and($result['sgst_paise'])->toBe(0);
});

it('charges no GST for a composition dealer', function () {
    $calc = new GstCalculator;

    $result = $calc->calculate(
        [['taxable_paise' => 100000, 'gst_rate' => 5.0]],
        Invoice::TREATMENT_COMPOSITION,
        interState: false,
    );

    expect($result['tax_paise'])->toBe(0)
        ->and($result['cgst_paise'])->toBe(0)
        ->and($result['sgst_paise'])->toBe(0)
        ->and($result['igst_paise'])->toBe(0);
});

it('puts the odd paise on SGST when the tax does not split evenly', function () {
    $calc = new GstCalculator;

    // 12% of 12345 = 1481.4 → 1481 paise; split 740 + 741.
    $result = $calc->calculate(
        [['taxable_paise' => 12345, 'gst_rate' => 12.0]],
        Invoice::TREATMENT_REGULAR,
        interState: false,
    );

    expect($result['tax_paise'])->toBe(1481)
        ->and($result['cgst_paise'])->toBe(740)
        ->and($result['sgst_paise'])->toBe(741);
});
