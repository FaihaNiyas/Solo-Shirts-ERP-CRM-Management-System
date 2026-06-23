<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Reports;

use App\Modules\Inventory\Models\FabricRoll;

/**
 * Fabric consumption per roll in a branch (received minus remaining).
 */
final class FabricConsumptionReport implements ReportInterface
{
    public function kind(): string
    {
        return 'fabric_consumption';
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return ['Roll ID', 'Received (m)', 'Remaining (m)', 'Consumed (m)'];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<list<string|int>>
     */
    public function rows(array $params, int $branchId): array
    {
        return FabricRoll::query()->withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->latest('id')
            ->limit(5000)
            ->get()
            ->map(function (FabricRoll $roll): array {
                $received = (float) $roll->received_length_metres;
                $remaining = (float) $roll->remaining_metres;

                return [
                    $roll->id,
                    number_format($received, 2, '.', ''),
                    number_format($remaining, 2, '.', ''),
                    number_format($received - $remaining, 2, '.', ''),
                ];
            })
            ->all();
    }
}
