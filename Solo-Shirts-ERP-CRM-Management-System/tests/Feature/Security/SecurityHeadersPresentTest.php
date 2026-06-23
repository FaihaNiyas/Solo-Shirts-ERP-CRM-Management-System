<?php

declare(strict_types=1);

use App\Modules\Shared\Services\HealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('emits the documented security headers on every API response', function () {
    $this->mock(HealthService::class, function ($mock) {
        $mock->shouldReceive('snapshot')->andReturn([
            'php' => PHP_VERSION, 'laravel' => app()->version(),
            'db' => true, 'redis' => true, 'queue' => true, 'commit' => 'abc1234',
        ]);
    });

    $response = $this->getJson('/api/v1/health')->assertOk();

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'no-referrer');

    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'none'")
        ->and($response->headers->get('Strict-Transport-Security'))->toContain('max-age=');
});

it('emits security headers even on error responses', function () {
    seedRoles();
    $branch = makeBranch(['code' => 'HQ']);
    $tailor = makeUser($branch, 'Tailor');

    $this->withHeaders(bearer($tailor))
        ->getJson('/api/v1/audit/activities')
        ->assertForbidden()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});
