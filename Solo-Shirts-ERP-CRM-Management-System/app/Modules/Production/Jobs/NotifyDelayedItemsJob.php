<?php

declare(strict_types=1);

namespace App\Modules\Production\Jobs;

use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Models\ProductionNotification;
use App\Modules\Production\Services\ProductionNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Daily scan that notifies section supervisors of items past their delivery date
 * (Kanban Phase F). Deduped: an item with an existing unread "delayed"
 * notification is skipped, so a still-late item isn't re-notified every day.
 */
final class NotifyDelayedItemsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** The active shop-floor stages that can be delayed. */
    private const ACTIVE = [
        OrderItem::STATE_FABRIC_ALLOCATED,
        OrderItem::STATE_CUTTING,
        OrderItem::STATE_TAILORING,
        OrderItem::STATE_KAJA_BUTTON,
        OrderItem::STATE_FINISHING,
        OrderItem::STATE_QC,
        OrderItem::STATE_REWORK,
        OrderItem::STATE_PACKING,
        OrderItem::STATE_READY_FOR_DELIVERY,
    ];

    public function handle(ProductionNotifier $notifier): void
    {
        $today = now()->toDateString();

        OrderItem::query()->withoutGlobalScopes()
            ->whereIn('state', self::ACTIVE)
            ->whereHas('order', fn ($q) => $q->withoutGlobalScopes()->whereDate('expected_delivery_date', '<', $today))
            ->get(['id', 'branch_id', 'state', 'item_code'])
            ->each(function (OrderItem $item) use ($notifier): void {
                $alreadyNotified = ProductionNotification::query()->withoutGlobalScopes()
                    ->where('order_item_id', $item->id)
                    ->where('type', ProductionNotification::TYPE_DELAYED)
                    ->whereNull('read_at')
                    ->exists();

                if ($alreadyNotified) {
                    return;
                }

                $recipients = $notifier->supervisorIds((int) $item->branch_id, (string) $item->state);
                $notifier->notify(
                    (int) $item->branch_id,
                    $recipients,
                    ProductionNotification::TYPE_DELAYED,
                    'Order delayed',
                    trim(($item->item_code ?? 'An item') . ' is past its delivery date.'),
                    $item->id,
                );
            });
    }
}
