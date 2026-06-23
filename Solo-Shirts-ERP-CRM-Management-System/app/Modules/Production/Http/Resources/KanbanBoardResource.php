<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Resources;

use App\Modules\Order\Models\OrderItem;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Serializes the kanban board: one column per workflow state, in workflow order,
 * each carrying its item count and item cards.
 */
final class KanbanBoardResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, list<OrderItem>> $board */
        $board = $this->resource;

        $columns = [];

        foreach ($board as $state => $items) {
            $columns[] = [
                'state' => $state,
                'count' => count($items),
                'items' => ProductionItemResource::collection($items)->resolve(),
            ];
        }

        return ['columns' => $columns];
    }
}
