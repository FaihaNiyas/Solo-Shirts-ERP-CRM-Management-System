<?php

declare(strict_types=1);

use App\Modules\Finance\Models\CreditNote;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Services\BalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

it('computes outstanding as invoice total minus payments minus credit notes', function () {
    $order = deliverableOrder($this->branch);
    $invoice = makeInvoice($this->branch, $order, ['total_paise' => 100000]);

    Payment::factory()->for($this->branch)->create([
        'invoice_id' => $invoice->id,
        'amount_paise' => 30000,
    ]);

    CreditNote::factory()->for($this->branch)->create([
        'invoice_id' => $invoice->id,
        'total_paise' => 20000,
    ]);

    $balances = app(BalanceService::class);

    // 100000 − 30000 − 20000 = 50000
    expect($balances->outstandingForInvoice($invoice))->toBe(50000);

    $order = $balances->outstandingForOrder($invoice->order_id);
    expect($order['invoiced_paise'])->toBe(100000)
        ->and($order['paid_paise'])->toBe(30000)
        ->and($order['credited_paise'])->toBe(20000)
        ->and($order['outstanding_paise'])->toBe(50000);
});

it('exposes the order outstanding balance over the API', function () {
    $accountant = makeUser($this->branch, 'Accountant');
    $order = deliverableOrder($this->branch);
    $invoice = makeInvoice($this->branch, $order, ['total_paise' => 80000]);

    Payment::factory()->for($this->branch)->create([
        'invoice_id' => $invoice->id,
        'amount_paise' => 80000,
    ]);

    $this->withHeaders(bearer($accountant))
        ->getJson("/api/v1/finance/orders/{$order->id}/outstanding-balance")
        ->assertOk()
        ->assertJsonPath('data.outstanding_paise', 0);
});
