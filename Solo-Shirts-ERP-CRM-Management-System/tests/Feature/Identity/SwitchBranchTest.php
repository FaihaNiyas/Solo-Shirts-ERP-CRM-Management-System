<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branchA = makeBranch(['code' => 'A']);
    $this->branchB = makeBranch(['code' => 'B']);
});

it('lets an Owner switch branch context so reads scope to the new branch', function () {
    $owner = makeUser($this->branchA, 'Owner');
    makeUser($this->branchA, 'Tailor', ['name' => 'A Tailor']);
    makeUser($this->branchB, 'Tailor', ['name' => 'B Tailor']);

    $headers = bearer($owner);

    // Switch into branch B.
    $this->withHeaders($headers)
        ->postJson('/api/v1/auth/switch-branch', ['branch_id' => $this->branchB->id])
        ->assertOk();

    // Now reads are scoped to branch B only.
    $response = $this->withHeaders($headers)->getJson('/api/v1/users')->assertOk();
    $names = collect($response->json('data'))->pluck('name');

    expect($names)->toContain('B Tailor')->not->toContain('A Tailor');

    // The Owner's underlying branch_id is unchanged.
    expect($owner->fresh()->branch_id)->toBe($this->branchA->id);
});

it('forbids staff from switching branch', function () {
    $admin = makeUser($this->branchA, 'Admin');

    $this->withHeaders(bearer($admin))
        ->postJson('/api/v1/auth/switch-branch', ['branch_id' => $this->branchB->id])
        ->assertForbidden();
});
