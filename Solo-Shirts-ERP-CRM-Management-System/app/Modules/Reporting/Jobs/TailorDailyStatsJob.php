<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Jobs;

use App\Modules\Production\Models\TailorAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Nightly tailor rollup: counts each tailor's completed assignments for the day
 * and records them on the activity log for the performance dashboard.
 */
final class TailorDailyStatsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly ?string $onDate = null) {}

    public function handle(): void
    {
        $day = ($this->onDate !== null ? Carbon::parse($this->onDate) : now()->subDay())->toDateString();

        /** @var array<int|string, int> $completed */
        $completed = TailorAssignment::query()->withoutGlobalScopes()
            ->whereNotNull('completed_at')
            ->whereDate('completed_at', $day)
            ->selectRaw('tailor_id, COUNT(*) as total')
            ->groupBy('tailor_id')
            ->pluck('total', 'tailor_id')
            ->map(fn ($v): int => (int) $v)
            ->all();

        activity('reporting')
            ->event('tailor-daily-stats')
            ->withProperties(['on_date' => $day, 'completed' => $completed])
            ->log('tailor daily stats computed');
    }
}
