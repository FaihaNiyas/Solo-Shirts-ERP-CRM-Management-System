<?php

declare(strict_types=1);

use App\Modules\Finance\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->accountant = makeUser($this->branch, 'Accountant');
});

it('stores the UPI ID encrypted at rest but decrypts it through the model', function () {
    $invoice = makeInvoice($this->branch, null, ['total_paise' => 100000]);

    $response = $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/finance/payments', [
            'invoice_id' => $invoice->id,
            'method' => 'upi',
            'amount_paise' => 50000,
            'upi_id' => 'customer@okhdfcbank',
        ])
        ->assertCreated();

    $id = (int) $response->json('data.id');

    // Raw column is ciphertext — never the plaintext UPI handle.
    $raw = (string) DB::table('payments')->where('id', $id)->value('upi_id');
    expect($raw)->not->toBe('customer@okhdfcbank')
        ->and($raw)->not->toContain('customer@okhdfcbank');

    // The cast transparently decrypts it back.
    expect(Payment::query()->findOrFail($id)->upi_id)->toBe('customer@okhdfcbank');

    // And it is never serialized in the API resource.
    $response->assertJsonMissingPath('data.upi_id');
});
