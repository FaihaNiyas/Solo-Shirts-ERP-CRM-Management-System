<?php

declare(strict_types=1);

use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Models\DeliveryOtp;
use App\Modules\Delivery\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->staff = makeUser($this->branch, 'Delivery Staff');
});

it('increments attempts on each wrong OTP and locks after the fifth (423)', function () {
    fakeNotifications();
    $delivery = makeDelivery($this->branch);

    dispatchDelivery($this, $this->staff, $delivery->id)->assertOk();

    // Capture the issued expiry so we can prove a wrong attempt never mutates it
    // (regression guard for QA-001: a TIMESTAMP column with ON UPDATE
    // CURRENT_TIMESTAMP would silently reset expires_at on every row update).
    $issuedExpiry = DeliveryOtp::query()->where('delivery_id', $delivery->id)->sole()->expires_at;

    // 'WRONG1' can never equal a 6-digit numeric OTP.
    for ($i = 1; $i <= OtpService::MAX_ATTEMPTS - 1; $i++) {
        confirmDelivery($this, $this->staff, $delivery->id, 'WRONG1')
            ->assertStatus(422)
            ->assertJsonPath('code', 'OTP_INVALID');

        /** @var DeliveryOtp $otp */
        $otp = DeliveryOtp::query()->where('delivery_id', $delivery->id)->sole();
        expect($otp->attempts)->toBe($i);

        // The expiry must be untouched by the failed-attempt UPDATE.
        expect($otp->expires_at->equalTo($issuedExpiry))->toBeTrue();
    }

    // The fifth wrong attempt locks the code.
    confirmDelivery($this, $this->staff, $delivery->id, 'WRONG1')
        ->assertStatus(423)
        ->assertJsonPath('code', 'OTP_LOCKED');
});

it('stays locked until a re-dispatch issues a fresh code', function () {
    $fake = fakeNotifications();
    $delivery = makeDelivery($this->branch);

    dispatchDelivery($this, $this->staff, $delivery->id)->assertOk();

    for ($i = 1; $i <= OtpService::MAX_ATTEMPTS; $i++) {
        confirmDelivery($this, $this->staff, $delivery->id, 'WRONG1');
    }

    // Even a correct guess is refused while locked — a new dispatch is required.
    confirmDelivery($this, $this->staff, $delivery->id, (string) $fake->lastOtp())
        ->assertStatus(423)
        ->assertJsonPath('code', 'OTP_LOCKED');

    // Re-dispatch issues a fresh OTP; the new code confirms.
    dispatchDelivery($this, $this->staff, $delivery->id)->assertOk();

    confirmDelivery($this, $this->staff, $delivery->id, (string) $fake->lastOtp())
        ->assertOk()
        ->assertJsonPath('data.status', Delivery::STATUS_DELIVERED);
});
