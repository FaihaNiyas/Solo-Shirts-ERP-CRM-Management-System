<?php

declare(strict_types=1);

it('returns a 422 standard envelope with code VALIDATION_FAILED on invalid input', function () {
    $response = $this->postJson('/api/v1/_smoke/validate', []);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'code' => 'VALIDATION_FAILED',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'code',
            'errors' => ['name'],
            'request_id',
        ]);
});

it('passes validation when input is valid', function () {
    $this->postJson('/api/v1/_smoke/validate', ['name' => 'Kurta'])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'data' => ['name' => 'Kurta'],
        ]);
});
