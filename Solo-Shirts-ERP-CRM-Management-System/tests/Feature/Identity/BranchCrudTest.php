<?php

declare(strict_types=1);

use App\Modules\Identity\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
});

it('lets an Owner create, list and update branches', function () {
    $hq = makeBranch(['code' => 'HQ']);
    $owner = makeUser($hq, 'Owner');

    $this->withHeaders(bearer($owner))
        ->postJson('/api/v1/branches', [
            'code' => 'BR2',
            'name' => 'Second Branch',
        ])
        ->assertCreated()
        ->assertJson(['success' => true, 'data' => ['code' => 'BR2']]);

    $this->withHeaders(bearer($owner))
        ->getJson('/api/v1/branches')
        ->assertOk()
        ->assertJsonPath('success', true);

    $created = Branch::query()->where('code', 'BR2')->firstOrFail();

    $this->withHeaders(bearer($owner))
        ->putJson("/api/v1/branches/{$created->id}", ['name' => 'Renamed Branch'])
        ->assertOk()
        ->assertJson(['data' => ['name' => 'Renamed Branch']]);
});

it('forbids non-owners from creating branches', function () {
    $hq = makeBranch(['code' => 'HQ']);
    $admin = makeUser($hq, 'Admin');

    $this->withHeaders(bearer($admin))
        ->postJson('/api/v1/branches', ['code' => 'NOPE', 'name' => 'X'])
        ->assertForbidden();
});
