<?php

declare(strict_types=1);

namespace App\Modules\Shared\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Verifies the liveness of the application's downstream dependencies. Every
 * check is defensive: it returns a boolean and never throws, so the health
 * endpoint can always render a structured response instead of a 500.
 */
class HealthService
{
    private static ?string $commit = null;

    /**
     * @return array{php: string, laravel: string, db: bool, redis: bool, queue: bool, commit: string}
     */
    public function snapshot(): array
    {
        return [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'db' => $this->checkDb(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'commit' => $this->commit(),
        ];
    }

    public function checkDb(): bool
    {
        return $this->guard(function (): bool {
            DB::connection()->select('select 1');

            return true;
        });
    }

    public function checkRedis(): bool
    {
        return $this->guard(function (): bool {
            $pong = Redis::connection()->ping();

            // predis returns the Status object/string "PONG"; phpredis returns true.
            return $pong === true || (string) $pong === 'PONG' || (string) $pong === '+PONG';
        });
    }

    public function checkQueue(): bool
    {
        return $this->guard(function (): bool {
            // size() forces the driver to actually reach its backend.
            Queue::connection()->size();

            return true;
        });
    }

    /**
     * Deployed commit SHA. Prefers APP_COMMIT; otherwise resolves the short
     * git SHA once and caches it for the process lifetime.
     */
    public function commit(): string
    {
        $fromEnv = config('app.commit');
        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }

        if (self::$commit !== null) {
            return self::$commit;
        }

        $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $sha = @exec("git rev-parse --short HEAD 2>{$null}") ?: 'unknown';

        return self::$commit = $sha;
    }

    /**
     * Run a probe, swallowing any failure into false so the endpoint never
     * 500s. Per-probe time bounding is enforced at the connection level (see
     * the 1s connect/read timeouts on the redis + database configs) rather than
     * via set_time_limit, which bounds the whole script and raises an
     * uncatchable fatal on expiry.
     *
     * @param  callable(): bool  $probe
     */
    private function guard(callable $probe): bool
    {
        try {
            return $probe();
        } catch (Throwable) {
            return false;
        }
    }
}
