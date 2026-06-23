<?php

declare(strict_types=1);

use App\Modules\Shared\Services\HealthService;

it('reports a deep health check of downstream dependencies', function () {
    // The real probes are covered in HealthCheckTest; here we assert the deep
    // snapshot contract (Redis is not running locally, so it is mocked up).
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

    $response = $this->getJson('/api/v1/health')->assertOk();

    $response->assertJsonFragment(['db' => true])
        ->assertJsonFragment(['queue' => true])
        ->assertJsonFragment(['redis' => true])
        ->assertJsonStructure(['data' => ['php', 'laravel', 'db', 'redis', 'queue', 'commit']]);
});
