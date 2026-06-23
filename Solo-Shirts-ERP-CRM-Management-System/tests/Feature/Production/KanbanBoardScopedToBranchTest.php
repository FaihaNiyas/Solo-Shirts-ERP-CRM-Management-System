<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branchA = makeBranch(['code' => 'HQ']);
    $this->branchB = makeBranch(['code' => 'BR2']);
    $this->user = makeUser($this->branchA, 'Production Supervisor');
});

it('groups items by state and only shows the active branch', function () {
    productionItem($this->branchA, 'cutting');
    productionItem($this->branchA, 'cutting');
    productionItem($this->branchA, 'qc');
    // Items in another branch must never appear on this board.
    productionItem($this->branchB, 'cutting');
    productionItem($this->branchB, 'tailoring');

    $response = $this->withHeaders(bearer($this->user))
        ->getJson('/api/v1/production/board')
        ->assertOk();

    $columns = collect($response->json('data.columns'))->keyBy('state');

    expect($columns['cutting']['count'])->toBe(2)
        ->and($columns['qc']['count'])->toBe(1)
        ->and($columns['tailoring']['count'])->toBe(0)
        ->and($columns->sum('count'))->toBe(3);
});

it('exposes every workflow state as a column even when empty', function () {
    productionItem($this->branchA, 'cutting');

    $response = $this->withHeaders(bearer($this->user))
        ->getJson('/api/v1/production/board')
        ->assertOk();

    $states = collect($response->json('data.columns'))->pluck('state')->all();

    expect($states)->toBe([
        'draft', 'fabric_allocated', 'cutting', 'tailoring', 'kaja_button',
        'finishing', 'qc', 'rework', 'packing', 'ready_for_delivery',
        'delivered', 'cancelled',
    ]);
});
