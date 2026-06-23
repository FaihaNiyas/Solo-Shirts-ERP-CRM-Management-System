<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Jobs;

use App\Modules\Production\Models\ProductionTransition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Nightly production rollup: counts the day's state transitions per target
 * state and records them on the activity log for trend dashboards.
 */
final class ProductionRollupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly ?string $onDate = null) {}

    public function handle(): void
    {
        $day = ($this->onDate !== null ? Carbon::parse($this->onDate) : now()->subDay())->toDateString();

        /** @var array<string, int> $counts */
        $counts = ProductionTransition::query()->withoutGlobalScopes()
            ->whereDate('occurred_at', $day)
            ->selectRaw('to_state, COUNT(*) as total')
            ->groupBy('to_state')
            ->pluck('total', 'to_state')
            ->map(fn ($v): int => (int) $v)
            ->all();

        activity('reporting')
            ->event('production-rollup')
            ->withProperties(['on_date' => $day, 'transitions' => $counts])
            ->log('production rollup computed');
    }
}
