<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Regression guard for the QA-001 bug class. Laravel manages created_at /
 * updated_at in PHP, so NO column should carry MySQL/MariaDB's implicit
 * `ON UPDATE CURRENT_TIMESTAMP` — which silently rewrites a value on every row
 * UPDATE. A new first-`TIMESTAMP NOT NULL` column would reintroduce it; this
 * test fails the build if that happens.
 */
uses(RefreshDatabase::class);

it('has no column carrying ON UPDATE CURRENT_TIMESTAMP', function () {
    $database = DB::getDatabaseName();

    $offenders = DB::table('information_schema.COLUMNS')
        ->where('TABLE_SCHEMA', $database)
        ->where('EXTRA', 'like', '%on update%')
        ->get(['TABLE_NAME', 'COLUMN_NAME'])
        ->map(fn ($r) => "{$r->TABLE_NAME}.{$r->COLUMN_NAME}")
        ->all();

    expect($offenders)->toBe(
        [],
        'These columns carry ON UPDATE CURRENT_TIMESTAMP and will silently reset on any '
        . 'row update. Declare them as dateTime (not timestamp) so MySQL/MariaDB does not '
        . 'attach the implicit ON UPDATE: ' . implode(', ', $offenders)
    );
});
