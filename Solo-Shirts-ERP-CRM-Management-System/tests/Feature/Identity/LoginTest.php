<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

function login(array $payload): TestResponse
{
    return test()->postJson('/api/v1/auth/login', $payload);
}

it('logs in with valid credentials and returns a token + abilities', function () {
    $user = makeUser($this->branch, 'Tailor', [
        'email' => 'tailor@hq.test',
        'password' => Hash::make('secret123'),
    ]);

    login(['email' => 'tailor@hq.test', 'password' => 'secret123'])
        ->assertOk()
        ->assertJson(['success' => true])
        ->assertJsonStructure(['data' => ['token', 'token_type', 'user', 'abilities']]);
});

it('blocks a privileged role that has not enrolled in 2FA when enforcement is on', function () {
    // Production/staging enforce 2FA for these roles; emulate that here.
    config(['identity.two_factor_required_roles' => ['Accountant']]);

    makeUser($this->branch, 'Accountant', [
        'email' => 'acc2@hq.test',
        'password' => Hash::make('secret123'),
        // No two_factor_confirmed_at — 2FA is not enabled.
    ]);

    login(['email' => 'acc2@hq.test', 'password' => 'secret123'])
        ->assertStatus(403)
        ->assertJsonPath('code', 'TWO_FACTOR_SETUP_REQUIRED');
});

it('still lets a non-privileged role log in without 2FA under enforcement', function () {
    config(['identity.two_factor_required_roles' => ['Accountant']]);

    makeUser($this->branch, 'Tailor', [
        'email' => 'tailor2@hq.test',
        'password' => Hash::make('secret123'),
    ]);

    login(['email' => 'tailor2@hq.test', 'password' => 'secret123'])->assertOk();
});

it('rejects a wrong password with 401 INVALID_CREDENTIALS', function () {
    makeUser($this->branch, 'Tailor', [
        'email' => 'tailor@hq.test',
        'password' => Hash::make('secret123'),
    ]);

    login(['email' => 'tailor@hq.test', 'password' => 'wrong'])
        ->assertStatus(401)
        ->assertJson(['code' => 'INVALID_CREDENTIALS']);
});

it('refuses inactive users', function () {
    makeUser($this->branch, 'Tailor', [
        'email' => 'off@hq.test',
        'password' => Hash::make('secret123'),
        'is_active' => false,
    ]);

    login(['email' => 'off@hq.test', 'password' => 'secret123'])
        ->assertStatus(403)
        ->assertJson(['code' => 'ACCOUNT_INACTIVE']);
});

it('locks out after 5 failed attempts (per email + ip)', function () {
    makeUser($this->branch, 'Tailor', [
        'email' => 'tailor@hq.test',
        'password' => Hash::make('secret123'),
    ]);

    for ($i = 0; $i < 5; $i++) {
        login(['email' => 'tailor@hq.test', 'password' => 'wrong'])->assertStatus(401);
    }

    login(['email' => 'tailor@hq.test', 'password' => 'secret123'])
        ->assertStatus(429)
        ->assertJson(['code' => 'ACCOUNT_LOCKED']);
});

it('requires a valid OTP for an Accountant with 2FA enabled', function () {
    $secret = (new Google2FA)->generateSecretKey();

    $user = makeUser($this->branch, 'Accountant', [
        'email' => 'acc@hq.test',
        'password' => Hash::make('secret123'),
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    // Missing OTP.
    login(['email' => 'acc@hq.test', 'password' => 'secret123'])
        ->assertStatus(401)
        ->assertJson(['code' => 'TWO_FACTOR_REQUIRED']);

    // Wrong OTP.
    login(['email' => 'acc@hq.test', 'password' => 'secret123', 'otp' => '000000'])
        ->assertStatus(401)
        ->assertJson(['code' => 'INVALID_OTP']);

    // Correct OTP.
    $otp = (new Google2FA)->getCurrentOtp($secret);
    login(['email' => 'acc@hq.test', 'password' => 'secret123', 'otp' => $otp])
        ->assertOk()
        ->assertJson(['success' => true]);
});

it('records every login attempt (success and failure) in login_attempts', function () {
    makeUser($this->branch, 'Tailor', [
        'email' => 'tailor@hq.test',
        'password' => Hash::make('secret123'),
    ]);

    login(['email' => 'tailor@hq.test', 'password' => 'wrong'])->assertStatus(401);
    login(['email' => 'tailor@hq.test', 'password' => 'secret123'])->assertOk();

    $this->assertDatabaseHas('login_attempts', ['email' => 'tailor@hq.test', 'success' => false]);
    $this->assertDatabaseHas('login_attempts', ['email' => 'tailor@hq.test', 'success' => true]);
});
