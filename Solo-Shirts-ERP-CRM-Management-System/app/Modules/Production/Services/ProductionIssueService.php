<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Events\ProductionIssueReported;
use App\Modules\Production\Exceptions\ProductionException;
use App\Modules\Production\Models\ProductionIssue;
use Illuminate\Support\Facades\DB;

/**
 * Raises and resolves production issues. An issue is a parallel flag on an item —
 * it records a problem at the item's current stage but never moves the production
 * state, which stays owned by the state machine.
 */
final class ProductionIssueService
{
    public function report(OrderItem $item, string $issueType, string $description, User $actor): ProductionIssue
    {
        $issue = ProductionIssue::query()->create([
            'order_item_id' => $item->id,
            'branch_id' => $item->branch_id,
            'stage' => (string) $item->state,
            'issue_type' => $issueType,
            'description' => $description,
            'status' => ProductionIssue::STATUS_OPEN,
            'reported_by' => $actor->id,
        ]);

        event(new ProductionIssueReported(
            issueId: $issue->id,
            orderItemId: $item->id,
            stage: $issue->stage,
            issueType: $issueType,
            reportedBy: $actor->id,
            occurredAt: $issue->created_at ?? now(),
        ));

        return $issue;
    }

    public function resolve(ProductionIssue $issue, ?string $notes, User $actor): ProductionIssue
    {
        return DB::transaction(function () use ($issue, $notes, $actor): ProductionIssue {
            /** @var ProductionIssue $locked */
            $locked = ProductionIssue::query()->lockForUpdate()->findOrFail($issue->id);

            if ($locked->isResolved()) {
                throw ProductionException::issueAlreadyResolved();
            }

            $locked->update([
                'status' => ProductionIssue::STATUS_RESOLVED,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
                'resolution_notes' => $notes,
            ]);

            return $locked;
        });
    }
}
