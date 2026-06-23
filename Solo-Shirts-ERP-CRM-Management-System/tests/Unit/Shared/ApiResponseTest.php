<?php

declare(strict_types=1);

use App\Modules\Shared\Support\ApiResponse;

it('builds a success envelope with the standard shape', function () {
    $response = ApiResponse::success(['answer' => 42], 'Done', 201);

    $body = $response->getData(true);

    expect($response->getStatusCode())->toBe(201)
        ->and($body['success'])->toBeTrue()
        ->and($body['message'])->toBe('Done')
        ->and($body['data'])->toBe(['answer' => 42])
        ->and($body)->toHaveKey('request_id')
        ->and($body['request_id'])->toBeString()->not->toBeEmpty();
});

it('builds an error envelope with a stable code and field errors', function () {
    $response = ApiResponse::error('Nope', 'SOME_DOMAIN_CODE', ['field' => ['is bad']], 422);

    $body = $response->getData(true);

    expect($response->getStatusCode())->toBe(422)
        ->and($body['success'])->toBeFalse()
        ->and($body['message'])->toBe('Nope')
        ->and($body['code'])->toBe('SOME_DOMAIN_CODE')
        ->and($body['errors'])->toBe(['field' => ['is bad']])
        ->and($body)->toHaveKey('request_id');
});
