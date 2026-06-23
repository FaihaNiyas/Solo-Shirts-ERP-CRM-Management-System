<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
});

it('revokes all existing tokens when a user is assigned a new role', function () {
    $hq = makeBranch(['code' => 'HQ']);
    $admin = makeUser($hq, 'Admin');
    $target = makeUser($hq, 'Tailor');

    $targetHeaders = bearer($target);

    // Target's token works before the change.
    $this->withHeaders($targetHeaders)->getJson('/api/v1/auth/me')->assertOk();
    forgetAuth();

    // Admin assigns a new role to the target.
    $this->withHeaders(bearer($admin))
        ->postJson("/api/v1/users/{$target->id}/assign-role", ['role' => 'Cutting Master'])
        ->assertOk();
    forgetAuth();

    // The target's previous token is now invalid.
    $this->withHeaders($targetHeaders)->getJson('/api/v1/auth/me')->assertStatus(401);
});
