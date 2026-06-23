<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\FabricRoll;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Computes which (branch, fabric_type) pairs are below their configurable
 * per-type low-stock threshold. Shared by the API and the alert job.
 */
final class LowStockService
{
    /**
     * @return Collection<int, \stdClass>
     */
    public function lowStock(?int $branchId = null): Collection
    {
        return DB::table('fabric_rolls as r')
            ->join('fabric_types as t', 't.id', '=', 'r.fabric_type_id')
            ->where('r.status', FabricRoll::STATUS_ACTIVE)
            ->whereNull('r.deleted_at')
            ->when($branchId !== null, fn (Builder $q): Builder => $q->where('r.branch_id', $branchId))
            ->groupBy('r.branch_id', 't.id', 't.code', 't.name', 't.low_stock_threshold_metres')
            ->havingRaw('SUM(r.remaining_metres) < t.low_stock_threshold_metres')
            ->selectRaw('r.branch_id, t.id as fabric_type_id, t.code, t.name, '
                . 'SUM(r.remaining_metres) as total_remaining, t.low_stock_threshold_metres as threshold')
            ->get();
    }
}
