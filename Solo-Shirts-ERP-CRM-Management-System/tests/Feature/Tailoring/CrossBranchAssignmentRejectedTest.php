<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->supervisor = makeUser($this->branch, 'Production Supervisor');
});

it('cannot assign a bundle that belongs to another branch', function () {
    $other = makeBranch(['code' => 'BR2']);
    $foreignBundle = cutBundleFor($other);
    $tailor = makeUser($this->branch, 'Tailor');

    $this->withHeaders(bearer($this->supervisor))
        ->postJson('/api/v1/tailoring/assignments', [
            'bundle_id' => $foreignBundle->id,
            'tailor_id' => $tailor->id,
        ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_BUNDLE');
});

it('cannot assign to a tailor from another branch', function () {
    $bundle = cutBundleFor($this->branch);
    $other = makeBranch(['code' => 'BR2']);
    $foreignTailor = makeUser($other, 'Tailor');

    $this->withHeaders(bearer($this->supervisor))
        ->postJson('/api/v1/tailoring/assignments', [
            'bundle_id' => $bundle->id,
            'tailor_id' => $foreignTailor->id,
        ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_TAILOR');
});

it('cannot assign to an inactive tailor', function () {
    $bundle = cutBundleFor($this->branch);
    $inactive = makeUser($this->branch, 'Tailor', ['is_active' => false]);

    $this->withHeaders(bearer($this->supervisor))
        ->postJson('/api/v1/tailoring/assignments', [
            'bundle_id' => $bundle->id,
            'tailor_id' => $inactive->id,
        ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'TAILOR_INACTIVE');
});
