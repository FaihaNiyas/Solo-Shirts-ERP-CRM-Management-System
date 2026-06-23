<?php

declare(strict_types=1);

it('renders a domain exception as the standard error envelope with its code and status', function () {
    $response = $this->getJson('/api/v1/_smoke/domain-exception');

    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'code' => 'INSUFFICIENT_STOCK',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'code',
            'errors',
            'request_id',
        ]);
});
