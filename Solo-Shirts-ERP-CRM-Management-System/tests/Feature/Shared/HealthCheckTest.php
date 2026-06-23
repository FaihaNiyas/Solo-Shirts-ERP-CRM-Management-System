<?php

declare(strict_types=1);

use App\Modules\Shared\Services\HealthService;

it('returns 200 with the standard envelope when all dependencies are up', function () {
    $this->mock(HealthService::class, function ($mock) {
        $mock->shouldReceive('snapshot')->once()->andReturn([
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'db' => true,
            'redis' => true,
            'queue' => true,
            'commit' => 'abc1234',
        ]);
    });

    $response = $this->getJson('/api/v1/health');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'db' => true,
                'redis' => true,
                'queue' => true,
                'commit' => 'abc1234',
            ],
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['php', 'laravel', 'db', 'redis', 'queue', 'commit'],
            'request_id',
        ]);

    expect($response->json('request_id'))->not->toBeNull();
});

it('returns 503 with code HEALTH_DEPENDENCY_DOWN when the DB ping fails', function () {
    $this->mock(HealthService::class, function ($mock) {
        $mock->shouldReceive('snapshot')->once()->andReturn([
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'db' => false,
            'redis' => true,
            'queue' => true,
            'commit' => 'abc1234',
        ]);
    });

    $this->getJson('/api/v1/health')
        ->assertStatus(503)
        ->assertJson([
            'success' => false,
            'code' => 'HEALTH_DEPENDENCY_DOWN',
            'data' => ['db' => false],
        ])
        ->assertJsonStructure(['success', 'message', 'code', 'data', 'request_id']);
});

it('returns 503 when the Redis ping fails', function () {
    $this->mock(HealthService::class, function ($mock) {
        $mock->shouldReceive('snapshot')->once()->andReturn([
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'db' => true,
            'redis' => false,
            'queue' => true,
            'commit' => 'abc1234',
        ]);
    });

    $this->getJson('/api/v1/health')
        ->assertStatus(503)
        ->assertJson([
            'success' => false,
            'code' => 'HEALTH_DEPENDENCY_DOWN',
            'data' => ['redis' => false],
        ]);
});

it('throttles to 60 requests per minute (61st returns 429)', function () {
    $this->mock(HealthService::class, function ($mock) {
        $mock->shouldReceive('snapshot')->andReturn([
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'db' => true,
            'redis' => true,
            'queue' => true,
            'commit' => 'abc1234',
        ]);
    });

    for ($i = 0; $i < 60; $i++) {
        $this->getJson('/api/v1/health')->assertOk();
    }

    $this->getJson('/api/v1/health')->assertStatus(429);
});

it('never 500s when a real dependency is unreachable (returns structured 503)', function () {
    // No mocking: the real HealthService runs. Redis points at a closed port,
    // so checkRedis() must degrade to false and the endpoint must return a
    // clean 503 envelope rather than leaking a fatal as a 500.
    config()->set('database.redis.default.host', '127.0.0.1');
    config()->set('database.redis.default.port', 6390); // nothing listening here

    $this->getJson('/api/v1/health')
        ->assertStatus(503)
        ->assertJson([
            'success' => false,
            'code' => 'HEALTH_DEPENDENCY_DOWN',
            'data' => ['redis' => false],
        ]);
});

it('echoes the request id in both the X-Request-Id header and the JSON body', function () {
    $this->mock(HealthService::class, function ($mock) {
        $mock->shouldReceive('snapshot')->andReturn([
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'db' => true,
            'redis' => true,
            'queue' => true,
            'commit' => 'abc1234',
        ]);
    });

    $response = $this->getJson('/api/v1/health');

    $header = $response->headers->get('X-Request-Id');

    expect($header)->not->toBeNull()
        ->and($header)->toBe($response->json('request_id'));
});
