<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-computed daily rollup per branch. The dashboard reads exclusively from
 * here (never OLTP joins) so it stays fast on large datasets. Recomputed nightly
 * by DailyBranchStatsJob; UNIQUE(branch_id, on_date) makes the upsert idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_branch_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->date('on_date');
            $table->unsignedInteger('orders_received')->default(0);
            $table->unsignedInteger('orders_delivered')->default(0);
            $table->bigInteger('revenue_paise')->default(0);
            $table->unsignedInteger('defects')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'on_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_branch_stats');
    }
};
