<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('replays the original response for the same key and same body', function () {
    $key = 'idem-key-001';

    $first = $this->withHeader('Idempotency-Key', $key)
        ->postJson('/api/v1/_smoke/idempotent', ['payload' => 'a']);
    $first->assertOk();

    $second = $this->withHeader('Idempotency-Key', $key)
        ->postJson('/api/v1/_smoke/idempotent', ['payload' => 'a']);
    $second->assertOk();

    // Same nonce proves the cached response was replayed, not regenerated.
    expect($second->json('data.nonce'))->toBe($first->json('data.nonce'));

    $this->assertDatabaseCount('idempotency_keys', 1);
});

it('returns 409 IDEMPOTENCY_CONFLICT for the same key but a different body', function () {
    $key = 'idem-key-002';

    $this->withHeader('Idempotency-Key', $key)
        ->postJson('/api/v1/_smoke/idempotent', ['payload' => 'a'])
        ->assertOk();

    $this->withHeader('Idempotency-Key', $key)
        ->postJson('/api/v1/_smoke/idempotent', ['payload' => 'DIFFERENT'])
        ->assertStatus(409)
        ->assertJson(['success' => false, 'code' => 'IDEMPOTENCY_CONFLICT']);
});

it('returns 400 IDEMPOTENCY_KEY_REQUIRED when the header is missing on a whitelisted route', function () {
    $this->postJson('/api/v1/_smoke/idempotent', ['payload' => 'a'])
        ->assertStatus(400)
        ->assertJson(['success' => false, 'code' => 'IDEMPOTENCY_KEY_REQUIRED']);
});

it('scopes idempotency keys per user (same key, different users do not collide)', function () {
    $key = 'shared-key';

    $mine = $this->withHeader('Idempotency-Key', $key)
        ->postJson('/api/v1/_smoke/idempotent', ['payload' => 'a']);
    $mine->assertOk();

    $other = User::factory()->create();
    $theirs = $this->actingAs($other)
        ->withHeader('Idempotency-Key', $key)
        ->postJson('/api/v1/_smoke/idempotent', ['payload' => 'a']);

    $theirs->assertOk();
    expect($theirs->json('data.nonce'))->not->toBe($mine->json('data.nonce'));
    $this->assertDatabaseCount('idempotency_keys', 2);
});
