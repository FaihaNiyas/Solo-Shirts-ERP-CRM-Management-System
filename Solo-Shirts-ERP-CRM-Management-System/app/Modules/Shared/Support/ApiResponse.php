<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Builds the project-wide standard API envelope. Every success and error
 * response in the system flows through here so the shape never drifts.
 */
final class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'request_id' => self::requestId(),
        ], $status);
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public static function error(
        string $message,
        string $code,
        array $errors = [],
        int $status = 400,
        mixed $data = null,
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
            'code' => $code,
            'errors' => (object) $errors,
            'request_id' => self::requestId(),
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    /**
     * Resolve the request id assigned by AssignRequestId middleware, falling
     * back to a fresh UUID for contexts where the middleware did not run.
     */
    public static function requestId(): string
    {
        $id = request()->attributes->get('request_id');

        return is_string($id) && $id !== '' ? $id : (string) Str::uuid();
    }
}
