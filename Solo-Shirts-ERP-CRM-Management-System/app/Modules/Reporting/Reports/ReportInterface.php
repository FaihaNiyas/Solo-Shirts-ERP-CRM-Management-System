<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Reports;

/**
 * A report produces a tabular dataset (header row + data rows) for a branch.
 * The runner turns that into a CSV document. Params are validated upstream; an
 * implementation must never interpolate them into raw SQL.
 */
interface ReportInterface
{
    public function kind(): string;

    /**
     * @return list<string>
     */
    public function headers(): array;

    /**
     * @param  array<string, mixed>  $params
     * @return list<list<string|int>>
     */
    public function rows(array $params, int $branchId): array;
}
