<?php

declare(strict_types=1);

namespace App\Modules\Production\Listeners;

use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Events\OrderItemStateChanged;
use App\Modules\Production\Models\ProductionNotification;
use App\Modules\Production\Services\ProductionNotifier;

/**
 * In-app notifications on a production stage change (Kanban Phase F). Notifies the
 * supervisors of the destination section: a new item arriving (new_assignment), a
 * QC failure parking it in rework (qc_failed), or a finished item ready for
 * delivery (ready_for_delivery). Draft/cancelled/delivered edges notify no one.
 * Synchronous so it commits with the transition that fired it.
 */
final class SendProductionNotifications
{
    public function __construct(private readonly ProductionNotifier $notifier) {}

    public function handle(OrderItemStateChanged $event): void
    {
        $item = OrderItem::query()->withoutGlobalScopes()->find($event->orderItemId);
        if ($item === null) {
            return;
        }

        [$type, $title] = match (true) {
            $event->to === OrderItem::STATE_REWORK => [ProductionNotification::TYPE_QC_FAILED, 'QC failed — item sent to rework'],
            $event->to === OrderItem::STATE_READY_FOR_DELIVERY => [ProductionNotification::TYPE_READY, 'Item ready for delivery'],
            in_array($event->to, [OrderItem::STATE_DRAFT, OrderItem::STATE_CANCELLED, OrderItem::STATE_DELIVERED], true) => [null, null],
            default => [ProductionNotification::TYPE_NEW_ASSIGNMENT, 'New item in ' . ProductionNotifier::label($event->to)],
        };

        if ($type === null) {
            return;
        }

        $recipients = $this->notifier->supervisorIds((int) $item->branch_id, $event->to);
        $body = trim(($item->item_code ?? 'An item') . ' moved to ' . ProductionNotifier::label($event->to) . '.');

        $this->notifier->notify((int) $item->branch_id, $recipients, $type, $title, $body, $item->id, $event->actorId);
    }
}
