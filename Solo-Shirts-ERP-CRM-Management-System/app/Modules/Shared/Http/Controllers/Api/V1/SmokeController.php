<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Shared\Exceptions\InsufficientStockException;
use App\Modules\Shared\Http\Requests\SmokeFormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Non-production controller used by the Phase 2 feature tests to prove the base
 * classes, idempotency middleware, and domain-exception handler are wired.
 */
final class SmokeController extends BaseApiController
{
    /**
     * Returns a fresh nonce each call; under the 'idempotent' middleware a
     * replayed request returns the original nonce.
     */
    public function store(): JsonResponse
    {
        return $this->respond(['nonce' => Str::random(24)], 'Created');
    }

    public function validateInput(SmokeFormRequest $request): JsonResponse
    {
        /** @var array{name: string} $validated */
        $validated = $request->validated();

        return $this->respond(['name' => $validated['name']]);
    }

    public function domainError(): never
    {
        throw new InsufficientStockException;
    }
}
