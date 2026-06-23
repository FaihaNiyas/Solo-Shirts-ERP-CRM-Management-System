<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Jobs;

use App\Modules\Shared\Models\IdempotencyKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Hourly housekeeping: drop idempotency keys older than the replay window so the
 * table does not grow unbounded.
 */
final class PruneStaleIdempotencyKeysJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const RETENTION_HOURS = 24;

    public function handle(): int
    {
        return IdempotencyKey::query()
            ->where('created_at', '<', now()->subHours(self::RETENTION_HOURS))
            ->delete();
    }
}
