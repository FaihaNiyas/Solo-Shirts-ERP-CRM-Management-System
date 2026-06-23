<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Reports\Concerns;

use Illuminate\Database\Query\Builder;

/**
 * Applies the optional `date_from` / `date_to` (Y-m-d) report params to a query
 * column. The frontend reports page always sends these two keys; a report opts in
 * by calling this against the timestamp column it wants bounded. Values bind as
 * query parameters — never interpolated — so untrusted input is safe.
 */
trait FiltersByDateRange
{
    /**
     * @param  array<string, mixed>  $params
     */
    protected function applyDateRange(Builder $query, array $params, string $column): void
    {
        $from = $params['date_from'] ?? null;
        $to = $params['date_to'] ?? null;

        if (is_string($from) && $from !== '') {
            $query->whereDate($column, '>=', $from);
        }

        if (is_string($to) && $to !== '') {
            $query->whereDate($column, '<=', $to);
        }
    }
}
