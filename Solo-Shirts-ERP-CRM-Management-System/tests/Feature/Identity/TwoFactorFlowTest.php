<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

it('enables and confirms 2FA, after which the next login requires an OTP', function () {
    $user = makeUser($this->branch, 'Accountant', [
        'email' => 'acc@hq.test',
        'password' => Hash::make('secret123'),
    ]);

    $headers = bearer($user);

    // Enable returns a secret + otpauth payload; not yet confirmed.
    $enable = $this->withHeaders($headers)->postJson('/api/v1/auth/2fa/enable')->assertOk();
    $secret = $enable->json('data.secret');
    expect($secret)->toBeString()->not->toBeEmpty();

    expect($user->fresh()->two_factor_confirmed_at)->toBeNull();

    // Confirm with a valid OTP.
    $otp = (new Google2FA)->getCurrentOtp($secret);
    $this->withHeaders($headers)
        ->postJson('/api/v1/auth/2fa/confirm', ['otp' => $otp])
        ->assertOk();

    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();

    // A fresh login now requires the OTP.
    $this->postJson('/api/v1/auth/login', ['email' => 'acc@hq.test', 'password' => 'secret123'])
        ->assertStatus(401)
        ->assertJson(['code' => 'TWO_FACTOR_REQUIRED']);

    $otp2 = (new Google2FA)->getCurrentOtp($secret);
    $this->postJson('/api/v1/auth/login', [
        'email' => 'acc@hq.test',
        'password' => 'secret123',
        'otp' => $otp2,
    ])->assertOk();
});

it('requires both current password and OTP to disable 2FA', function () {
    $secret = (new Google2FA)->generateSecretKey();
    $user = makeUser($this->branch, 'Accountant', [
        'password' => Hash::make('secret123'),
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    $headers = bearer($user);

    // Password alone is not enough.
    $this->withHeaders($headers)
        ->postJson('/api/v1/auth/2fa/disable', ['password' => 'secret123'])
        ->assertStatus(422);

    // Correct password + OTP disables it.
    $otp = (new Google2FA)->getCurrentOtp($secret);
    $this->withHeaders($headers)
        ->postJson('/api/v1/auth/2fa/disable', ['password' => 'secret123', 'otp' => $otp])
        ->assertOk();

    expect($user->fresh()->two_factor_secret)->toBeNull();
});
