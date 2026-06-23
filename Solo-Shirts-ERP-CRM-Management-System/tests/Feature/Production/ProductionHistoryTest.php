<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Production Supervisor');
});

it('returns an item history in occurred_at order', function () {
    $item = productionItem($this->branch, 'draft');

    foreach (['fabric_allocated', 'cutting', 'tailoring'] as $to) {
        transitionItem($this, $this->user, $item->id, ['to' => $to])->assertOk();
    }

    $response = $this->withHeaders(bearer($this->user))
        ->getJson("/api/v1/production/items/{$item->id}/history")
        ->assertOk();

    $sequence = array_map(fn (array $row): string => $row['to_state'], $response->json('data'));

    expect($sequence)->toBe(['fabric_allocated', 'cutting', 'tailoring']);
});

it('returns the current state on the item detail endpoint', function () {
    $item = productionItem($this->branch, 'cutting');

    $this->withHeaders(bearer($this->user))
        ->getJson("/api/v1/production/items/{$item->id}")
        ->assertOk()
        ->assertJsonPath('data.state', 'cutting')
        ->assertJsonPath('data.allowed_transitions', ['tailoring', 'cancelled']);
});

it('forbids viewing production without the production.view permission', function () {
    $stranger = makeUser($this->branch, 'Measurement Staff');
    $item = productionItem($this->branch, 'cutting');

    $this->withHeaders(bearer($stranger))
        ->getJson("/api/v1/production/items/{$item->id}")
        ->assertForbidden();
});
