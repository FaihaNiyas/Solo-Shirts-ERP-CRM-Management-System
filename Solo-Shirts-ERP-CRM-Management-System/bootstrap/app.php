<?php

declare(strict_types=1);

use App\Modules\Finance\Console\Commands\BackfillPaymentAllocations;
use App\Modules\Shared\Console\Commands\BackupVerify;
use App\Modules\Shared\Console\Commands\CleanupDummyData;
use App\Modules\Shared\Console\Commands\GenerateOpenApi;
use App\Modules\Shared\Exceptions\DomainException;
use App\Modules\Shared\Http\Middleware\AssignRequestId;
use App\Modules\Shared\Http\Middleware\ForceJsonResponse;
use App\Modules\Shared\Http\Middleware\IdempotencyMiddleware;
use App\Modules\Shared\Http\Middleware\SecurityHeaders;
use App\Modules\Shared\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        BackfillPaymentAllocations::class,
        BackupVerify::class,
        CleanupDummyData::class,
        GenerateOpenApi::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'request.id' => AssignRequestId::class,
            'idempotent' => IdempotencyMiddleware::class,
        ]);

        // Every /api/* request is JSON-only and carries a request id.
        $middleware->api(prepend: [
            AssignRequestId::class,
            ForceJsonResponse::class,
        ]);

        // Security headers ride out on every API response.
        $middleware->api(append: [
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            // Business-rule violations carry their own status, code, and errors.
            if ($e instanceof DomainException) {
                return ApiResponse::error(
                    message: $e->getMessage(),
                    code: $e->errorCode(),
                    errors: $e->errors(),
                    status: $e->status(),
                );
            }

            [$status, $code] = match (true) {
                $e instanceof ValidationException => [422, 'VALIDATION_FAILED'],
                $e instanceof AuthenticationException => [401, 'UNAUTHENTICATED'],
                $e instanceof AuthorizationException => [403, 'FORBIDDEN'],
                $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => [404, 'NOT_FOUND'],
                $e instanceof TooManyRequestsHttpException => [429, 'TOO_MANY_REQUESTS'],
                $e instanceof HttpExceptionInterface => [$e->getStatusCode(), 'HTTP_ERROR'],
                default => [500, 'SERVER_ERROR'],
            };

            $errors = $e instanceof ValidationException ? $e->errors() : [];

            $message = $status === 500 && !config('app.debug')
                ? 'Server error.'
                : $e->getMessage();

            return ApiResponse::error(
                message: $message !== '' ? $message : 'Request failed.',
                code: $code,
                errors: $errors,
                status: $status,
            );
        });
    })->create();
