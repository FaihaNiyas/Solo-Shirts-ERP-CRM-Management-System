<?php

declare(strict_types=1);

namespace App\Modules\Production\Listeners;

use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Events\ProductionIssueReported;
use App\Modules\Production\Models\ProductionNotification;
use App\Modules\Production\Services\ProductionNotifier;

/**
 * In-app notification when an issue is raised against an item (Kanban Phase F).
 * Notifies the supervisors of the stage the issue was reported at, excluding the
 * reporter.
 */
final class SendIssueNotification
{
    public function __construct(private readonly ProductionNotifier $notifier) {}

    public function handle(ProductionIssueReported $event): void
    {
        $item = OrderItem::query()->withoutGlobalScopes()->find($event->orderItemId);
        if ($item === null) {
            return;
        }

        $recipients = $this->notifier->supervisorIds((int) $item->branch_id, $event->stage);
        $title = 'Issue reported: ' . ProductionNotifier::label($event->issueType);
        $body = trim(($item->item_code ?? 'An item') . ' flagged at ' . ProductionNotifier::label($event->stage) . '.');

        $this->notifier->notify(
            (int) $item->branch_id,
            $recipients,
            ProductionNotification::TYPE_ISSUE_REPORTED,
            $title,
            $body,
            $item->id,
            $event->reportedBy,
        );
    }
}
