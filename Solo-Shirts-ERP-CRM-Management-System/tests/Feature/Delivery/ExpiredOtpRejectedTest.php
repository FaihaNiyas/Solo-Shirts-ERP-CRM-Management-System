<?php

declare(strict_types=1);

use App\Modules\Delivery\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->staff = makeUser($this->branch, 'Delivery Staff');
});

it('rejects an OTP used after its expiry window (422 OTP_EXPIRED)', function () {
    $fake = fakeNotifications();
    $delivery = makeDelivery($this->branch);

    dispatchDelivery($this, $this->staff, $delivery->id)->assertOk();
    $otp = (string) $fake->lastOtp();

    // Move past the 10-minute window.
    $this->travel(OtpService::TTL_MINUTES + 1)->minutes();

    confirmDelivery($this, $this->staff, $delivery->id, $otp)
        ->assertStatus(422)
        ->assertJsonPath('code', 'OTP_EXPIRED');
});
