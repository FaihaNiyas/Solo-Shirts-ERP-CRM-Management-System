<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Jobs;

use App\Modules\Production\Models\QcDefectPhoto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Daily housekeeping: remove QC defect photos that were uploaded but never
 * attached to a defect (orphans from abandoned inspections).
 */
final class PruneOrphanQcPhotosJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const ORPHAN_GRACE_HOURS = 24;

    public function handle(): int
    {
        return QcDefectPhoto::query()->withoutGlobalScopes()
            ->whereNull('qc_defect_id')
            ->where('created_at', '<', now()->subHours(self::ORPHAN_GRACE_HOURS))
            ->delete();
    }
}
