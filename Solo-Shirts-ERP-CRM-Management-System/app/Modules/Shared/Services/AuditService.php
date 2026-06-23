<?php

declare(strict_types=1);

namespace App\Modules\Shared\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Thin wrapper over spatie/activitylog for business audit entries, so callers
 * record intent ("damage-approved") without re-stating the log-name plumbing.
 * Model-level change logging is configured on the models themselves; this is for
 * explicit, named business events.
 */
final class AuditService
{
    public const LOG_NAME = 'audit';

    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(string $event, Model $subject, array $properties = [], ?User $causer = null): void
    {
        activity(self::LOG_NAME)
            ->performedOn($subject)
            ->causedBy($causer)
            ->event($event)
            ->withProperties($properties)
            ->log($event);
    }
}
