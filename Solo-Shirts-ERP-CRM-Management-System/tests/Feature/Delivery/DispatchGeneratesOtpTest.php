<?php

declare(strict_types=1);

use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Models\DeliveryOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->staff = makeUser($this->branch, 'Delivery Staff');
});

it('stores only the OTP hash and never the raw code', function () {
    $fake = fakeNotifications();
    $delivery = makeDelivery($this->branch);

    dispatchDelivery($this, $this->staff, $delivery->id)
        ->assertOk()
        ->assertJsonPath('data.status', Delivery::STATUS_DISPATCHED);

    $raw = $fake->lastOtp();
    expect($raw)->toBeString()->toHaveLength(6);

    /** @var DeliveryOtp $otp */
    $otp = DeliveryOtp::query()->where('delivery_id', $delivery->id)->sole();

    // The stored value is a hash, not the plaintext, but it verifies.
    expect($otp->otp_hash)->not->toBe($raw)
        ->and(Hash::check($raw, $otp->otp_hash))->toBeTrue();

    // The raw code appears nowhere in the table.
    $this->assertDatabaseMissing('delivery_otps', ['otp_hash' => $raw]);

    expect($delivery->fresh()->dispatched_at)->not->toBeNull();
});

it('transmits the raw OTP over the notification channel exactly once', function () {
    $fake = fakeNotifications();
    $delivery = makeDelivery($this->branch);

    dispatchDelivery($this, $this->staff, $delivery->id)->assertOk();

    expect($fake->sent)->toHaveCount(1)
        ->and($fake->sent[0]['payload']['otp'])->toBe($fake->lastOtp());
});
