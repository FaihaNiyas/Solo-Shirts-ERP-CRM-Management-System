<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
});

it('rejects a token older than the 24h expiry window', function () {
    config()->set('sanctum.expiration', 60 * 24);

    $user = makeUser(makeBranch(), 'Tailor');
    $headers = bearer($user);

    // Fresh token works.
    $this->withHeaders($headers)->getJson('/api/v1/auth/me')->assertOk();
    forgetAuth();

    // Age the token past 24h.
    PersonalAccessToken::query()->update(['created_at' => now()->subHours(25)]);

    $this->withHeaders($headers)->getJson('/api/v1/auth/me')->assertStatus(401);
});
