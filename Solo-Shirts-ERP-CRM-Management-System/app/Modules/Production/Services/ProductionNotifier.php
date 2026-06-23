<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Modules\Production\Models\ProductionNotification;
use App\Modules\Production\Models\ProductionStageSupervisor;
use Illuminate\Support\Str;

/**
 * Creates in-app production notifications and resolves their recipients (the
 * supervisors of a section). Recipient resolution and inserts bypass the branch
 * global scope and take an explicit branch_id, so this works from event listeners
 * and console commands where no branch context is set.
 */
final class ProductionNotifier
{
    /**
     * Insert one notification per recipient (the actor who triggered the event is
     * excluded — you don't notify yourself).
     *
     * @param  list<int>  $userIds
     */
    public function notify(
        int $branchId,
        array $userIds,
        string $type,
        string $title,
        ?string $body,
        ?int $orderItemId,
        ?int $excludeUserId = null,
    ): void {
        $now = now();
        $rows = [];

        foreach (array_unique($userIds) as $userId) {
            if ($excludeUserId !== null && (int) $userId === $excludeUserId) {
                continue;
            }

            $rows[] = [
                'branch_id' => $branchId,
                'user_id' => (int) $userId,
                'order_item_id' => $orderItemId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'read_at' => null,
                'created_at' => $now,
            ];
        }

        if ($rows !== []) {
            ProductionNotification::query()->insert($rows);
        }
    }

    /**
     * The user ids supervising a stage in a branch.
     *
     * @return list<int>
     */
    public function supervisorIds(int $branchId, string $stage): array
    {
        return ProductionStageSupervisor::query()
            ->withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->where('stage', $stage)
            ->pluck('user_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /** Human label for a snake_case stage, for notification copy. */
    public static function label(string $stage): string
    {
        return Str::headline($stage);
    }
}
