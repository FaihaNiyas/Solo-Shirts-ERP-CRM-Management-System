<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Middleware;

use App\Modules\Shared\Services\IdempotencyService;
use App\Modules\Shared\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applied to side-effectful routes that opt into idempotency (the 'idempotent'
 * alias). Requires an Idempotency-Key header, then either runs the request once
 * and caches the response, or replays the cached response on a repeat.
 */
final class IdempotencyMiddleware
{
    private const METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(private readonly IdempotencyService $idempotency) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->getMethod(), self::METHODS, true)) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key');

        if ($key === null || trim($key) === '') {
            return ApiResponse::error(
                message: 'An Idempotency-Key header is required for this request.',
                code: 'IDEMPOTENCY_KEY_REQUIRED',
                status: 400,
            );
        }

        if (Auth::id() === null) {
            return ApiResponse::error(
                message: 'Authentication is required to use idempotency.',
                code: 'UNAUTHENTICATED',
                status: 401,
            );
        }

        $result = $this->idempotency->rememberOrExecute($key, function () use ($next, $request): array {
            $response = $next($request);

            return [
                'status' => $response->getStatusCode(),
                'body' => $this->decode($response),
            ];
        });

        return response()->json($result['body'], $result['status']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        $decoded = json_decode((string) $response->getContent(), true);

        return is_array($decoded) ? $decoded : [];
    }
}
