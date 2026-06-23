<?php

declare(strict_types=1);

namespace App\Modules\Shared\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Generates an OpenAPI 3.0 document by introspecting the registered API routes.
 * It documents the contract surface (paths, methods, tags, auth, the standard
 * success/error envelope) rather than per-field request bodies, giving clients
 * and tooling a single source of truth without hand-maintaining a huge spec.
 */
final class GenerateOpenApi extends Command
{
    protected $signature = 'openapi:generate {--output=docs/openapi.json}';

    protected $description = 'Generate an OpenAPI 3.0 spec from the registered API routes';

    public function handle(): int
    {
        $paths = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();

            if (!Str::startsWith($uri, 'api/v1/') || Str::contains($uri, '_smoke')) {
                continue;
            }

            $openApiPath = '/' . preg_replace('/\{(\w+)\??\}/', '{$1}', $uri);

            foreach ($this->httpMethods($route) as $method) {
                $paths[$openApiPath][$method] = $this->operation($route, $uri);
            }
        }

        ksort($paths);

        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Solo Shirts India ERP API',
                'version' => 'v1',
                'description' => 'Garment/tailoring production ERP. Every response uses the standard envelope '
                    . '(success, message, data, request_id) or its error form (success, message, code, errors, request_id). '
                    . 'Write endpoints accept an Idempotency-Key header; several require it.',
            ],
            'servers' => [['url' => '/', 'description' => 'Same-origin']],
            'security' => [['bearerAuth' => []]],
            'tags' => $this->tags($paths),
            'paths' => $paths,
            'components' => $this->components(),
        ];

        $output = (string) $this->option('output');
        $target = base_path($output);
        @mkdir(dirname($target), 0755, true);
        file_put_contents($target, json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        $this->info('OpenAPI spec written to ' . $output . ' (' . count($paths) . ' paths).');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function httpMethods(RoutingRoute $route): array
    {
        return collect($route->methods())
            ->map(fn (string $m): string => strtolower($m))
            ->reject(fn (string $m): bool => in_array($m, ['head', 'options'], true))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function operation(RoutingRoute $route, string $uri): array
    {
        $public = Str::contains($uri, ['auth/login', 'health']) || Str::contains($uri, 'download');

        $operation = [
            'tags' => [$this->tagFor($uri)],
            'summary' => $this->summaryFor($route),
            'operationId' => $route->getName() ?: strtolower(implode('_', $route->methods()) . '_' . Str::slug($uri)),
            'parameters' => $this->pathParameters($uri),
            'responses' => [
                '200' => $this->envelopeResponse('Success'),
                '201' => $this->envelopeResponse('Created'),
                '401' => $this->errorResponse('Unauthenticated'),
                '403' => $this->errorResponse('Forbidden'),
                '404' => $this->errorResponse('Not found'),
                '422' => $this->errorResponse('Validation failed'),
            ],
        ];

        if ($public) {
            $operation['security'] = [];
        }

        return $operation;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pathParameters(string $uri): array
    {
        preg_match_all('/\{(\w+)\??\}/', $uri, $matches);

        return collect($matches[1])->map(fn (string $name): array => [
            'name' => $name,
            'in' => 'path',
            'required' => true,
            'schema' => ['type' => 'string'],
        ])->all();
    }

    private function tagFor(string $uri): string
    {
        $segments = explode('/', $uri); // api / v1 / <group> / ...

        return $segments[2] ?? 'general';
    }

    private function summaryFor(RoutingRoute $route): string
    {
        $action = $route->getActionName();

        if ($action === 'Closure') {
            return 'Inline handler';
        }

        $short = class_basename(Str::before($action, '@'));
        $method = Str::contains($action, '@') ? Str::after($action, '@') : '__invoke';

        return Str::headline($method) . ' (' . $short . ')';
    }

    /**
     * @param  array<string, mixed>  $paths
     * @return list<array<string, string>>
     */
    private function tags(array $paths): array
    {
        return collect($paths)
            ->flatMap(fn (array $methods): array => collect($methods)->flatMap(fn (array $op): array => $op['tags'])->all())
            ->unique()
            ->sort()
            ->map(fn (string $tag): array => ['name' => $tag])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function envelopeResponse(string $description): array
    {
        return [
            'description' => $description,
            'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/SuccessEnvelope']]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function errorResponse(string $description): array
    {
        return [
            'description' => $description,
            'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ErrorEnvelope']]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function components(): array
    {
        return [
            'securitySchemes' => [
                'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'description' => 'Sanctum personal access token'],
            ],
            'parameters' => [
                'IdempotencyKey' => [
                    'name' => 'Idempotency-Key',
                    'in' => 'header',
                    'required' => false,
                    'schema' => ['type' => 'string', 'format' => 'uuid'],
                    'description' => 'Deduplicates a write; required on some endpoints (orders, payments, transitions).',
                ],
            ],
            'schemas' => [
                'SuccessEnvelope' => [
                    'type' => 'object',
                    'required' => ['success', 'message', 'request_id'],
                    'properties' => [
                        'success' => ['type' => 'boolean', 'example' => true],
                        'message' => ['type' => 'string', 'example' => 'OK'],
                        'data' => ['nullable' => true, 'description' => 'Endpoint-specific payload (object or array).'],
                        'request_id' => ['type' => 'string', 'format' => 'uuid'],
                    ],
                ],
                'ErrorEnvelope' => [
                    'type' => 'object',
                    'required' => ['success', 'message', 'code', 'request_id'],
                    'properties' => [
                        'success' => ['type' => 'boolean', 'example' => false],
                        'message' => ['type' => 'string'],
                        'code' => ['type' => 'string', 'example' => 'VALIDATION_FAILED'],
                        'errors' => ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']]],
                        'request_id' => ['type' => 'string', 'format' => 'uuid'],
                    ],
                ],
            ],
        ];
    }
}
