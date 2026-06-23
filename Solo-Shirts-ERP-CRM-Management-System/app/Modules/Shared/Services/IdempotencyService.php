<?php

declare(strict_types=1);

namespace App\Modules\Shared\Services;

use App\Modules\Shared\Exceptions\IdempotencyConflictException;
use App\Modules\Shared\Models\IdempotencyKey;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Persists the first response for a given (user, Idempotency-Key) and replays it
 * for any later request that reuses the key. A different body under the same key
 * is a conflict; a key claimed but not yet completed is "in flight".
 */
final class IdempotencyService
{
    private const TTL_HOURS = 24;

    /**
     * @param  Closure(): array{status: int, body: array<string, mixed>}  $fn
     * @return array{status: int, body: array<string, mixed>, replayed: bool}
     *
     * @throws AuthenticationException
     * @throws IdempotencyConflictException
     */
    public function rememberOrExecute(string $key, Closure $fn): array
    {
        $userId = Auth::id();

        if ($userId === null) {
            throw new AuthenticationException;
        }

        $request = request();
        $hash = $this->hashRequest($request);

        $existing = IdempotencyKey::query()
            ->where('user_id', $userId)
            ->where('key', $key)
            ->first();

        // A record past its TTL is treated as if it never existed.
        if ($existing !== null && $existing->created_at->lt(now()->subHours(self::TTL_HOURS))) {
            $existing->delete();
            $existing = null;
        }

        if ($existing !== null) {
            if ($existing->request_hash !== $hash) {
                throw new IdempotencyConflictException;
            }

            if ($existing->response_status === null) {
                throw IdempotencyConflictException::inFlight();
            }

            return [
                'status' => $existing->response_status,
                'body' => $this->decodeBody($existing->response_body),
                'replayed' => true,
            ];
        }

        // Claim the key. The (user_id, key) unique index makes this the
        // arbitration point under concurrency: the loser sees "in flight".
        try {
            $record = IdempotencyKey::create([
                'key' => $key,
                'user_id' => $userId,
                'method' => $request->getMethod(),
                'path' => $request->path(),
                'request_hash' => $hash,
                'response_status' => null,
                'response_body' => null,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw IdempotencyConflictException::inFlight();
        }

        try {
            $result = $fn();
        } catch (Throwable $e) {
            // Never leave a poisoned "in flight" row behind on failure.
            $record->delete();
            throw $e;
        }

        $record->update([
            'response_status' => $result['status'],
            'response_body' => json_encode($result['body'], JSON_THROW_ON_ERROR),
        ]);

        return [
            'status' => $result['status'],
            'body' => $result['body'],
            'replayed' => false,
        ];
    }

    private function hashRequest(Request $request): string
    {
        $payload = [
            'method' => $request->getMethod(),
            'path' => $request->path(),
            'body' => $request->all(),
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeBody(?string $body): array
    {
        if ($body === null || $body === '') {
            return [];
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }
}
