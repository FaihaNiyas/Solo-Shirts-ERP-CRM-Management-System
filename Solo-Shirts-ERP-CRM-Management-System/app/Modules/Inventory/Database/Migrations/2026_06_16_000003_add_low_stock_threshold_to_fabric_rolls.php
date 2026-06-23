<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 8A — an optional per-roll reorder threshold. When set, the roll flags as
 * low stock once its remaining metres fall below it. This is additive to the
 * existing per-fabric-type aggregate low-stock alert (fabric_types
 * .low_stock_threshold_metres) — the two coexist: per-type drives the alerts
 * report, per-roll drives the individual roll badge. Nullable so existing rolls
 * are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fabric_rolls', function (Blueprint $table) {
            $table->decimal('low_stock_threshold_metres', 8, 2)->nullable()->after('remaining_metres');
        });
    }

    public function down(): void
    {
        Schema::table('fabric_rolls', function (Blueprint $table) {
            $table->dropColumn('low_stock_threshold_metres');
        });
    }
};
