<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branchA = makeBranch(['code' => 'A']);
    $this->branchB = makeBranch(['code' => 'B']);
});

it('hides other-branch users from a branch-scoped admin list', function () {
    $adminA = makeUser($this->branchA, 'Admin');
    makeUser($this->branchA, 'Tailor', ['name' => 'A Tailor']);
    makeUser($this->branchB, 'Tailor', ['name' => 'B Tailor']);

    $response = $this->withHeaders(bearer($adminA))->getJson('/api/v1/users')->assertOk();

    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('A Tailor')
        ->and($names)->not->toContain('B Tailor');
});

it('forbids reading a user from another branch (403)', function () {
    $adminA = makeUser($this->branchA, 'Admin');
    $bUser = makeUser($this->branchB, 'Tailor');

    $this->withHeaders(bearer($adminA))
        ->getJson("/api/v1/users/{$bUser->id}")
        ->assertStatus(403);
});

it('forbids updating a user from another branch (403)', function () {
    $adminA = makeUser($this->branchA, 'Admin');
    $bUser = makeUser($this->branchB, 'Tailor');

    $this->withHeaders(bearer($adminA))
        ->putJson("/api/v1/users/{$bUser->id}", ['name' => 'Hacked'])
        ->assertStatus(403);
});

it('lets an Owner see users across all branches', function () {
    $owner = makeUser($this->branchA, 'Owner');
    makeUser($this->branchA, 'Tailor', ['name' => 'A Tailor']);
    makeUser($this->branchB, 'Tailor', ['name' => 'B Tailor']);

    $response = $this->withHeaders(bearer($owner))->getJson('/api/v1/users')->assertOk();

    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('A Tailor')->toContain('B Tailor');
});
