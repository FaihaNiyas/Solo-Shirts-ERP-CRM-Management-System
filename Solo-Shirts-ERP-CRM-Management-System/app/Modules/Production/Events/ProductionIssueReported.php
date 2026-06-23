<?php

declare(strict_types=1);

namespace App\Modules\Production\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Carbon;

/**
 * Emitted when a production issue is raised against an item. A seam for Phase F
 * notifications (notify the supervisor / manager); there is no listener yet.
 */
final class ProductionIssueReported
{
    use Dispatchable;

    public function __construct(
        public readonly int $issueId,
        public readonly int $orderItemId,
        public readonly string $stage,
        public readonly string $issueType,
        public readonly ?int $reportedBy,
        public readonly Carbon $occurredAt,
    ) {}
}
