<?php

declare(strict_types=1);

namespace App\Modules\Shared\Traits;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Records create/update activity for critical models on the (append-only)
 * activity_log under the 'audit' log name. Each model declares exactly which
 * attributes are audited via auditAttributes() — encrypted/secret columns
 * (phone, upi_id) are deliberately excluded so they never reach the log.
 *
 * @mixin Model
 */
trait AuditsChanges
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->auditAttributes())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('audit');
    }

    /**
     * @return list<string>
     */
    abstract protected function auditAttributes(): array;
}
