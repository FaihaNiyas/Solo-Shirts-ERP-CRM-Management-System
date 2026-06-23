<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-computed per-tailor daily rollups. Phase 17's scheduler fills these; until
 * then the performance service computes the same figures on demand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tailor_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('tailor_id')->constrained('users')->cascadeOnDelete();
            $table->date('on_date');
            $table->unsignedInteger('bundles_completed')->default(0);
            $table->decimal('avg_minutes_per_piece', 8, 2)->default(0);
            $table->unsignedInteger('rework_count')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'tailor_id', 'on_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tailor_daily_stats');
    }
};
