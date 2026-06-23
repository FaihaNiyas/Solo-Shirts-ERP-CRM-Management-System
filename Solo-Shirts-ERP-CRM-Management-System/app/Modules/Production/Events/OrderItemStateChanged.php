<?php

declare(strict_types=1);

namespace App\Modules\Production\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Carbon;

/**
 * Emitted once per successful production transition. Downstream listeners react
 * to it (audit log, delivery slotting, customer notifications).
 */
final class OrderItemStateChanged
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly int $orderItemId,
        public readonly string $from,
        public readonly string $to,
        public readonly ?int $actorId,
        public readonly Carbon $occurredAt,
        public readonly array $metadata = [],
    ) {}
}
