<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Order lifecycle gate (Phase 2.5). Separate from the item-derived production
 * `status`: this column governs whether an order is still being prepared at the
 * Front Desk counter (intake_preparation) or has been confirmed and released to
 * production (order_received), or cancelled.
 *
 * Defaults to 'order_received' so every pre-existing order — and any order
 * created outside the intake wizard — stays production-visible exactly as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('lifecycle_status', 30)->default('order_received')->after('source');
            $table->index(['branch_id', 'lifecycle_status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'lifecycle_status']);
            $table->dropColumn('lifecycle_status');
        });
    }
};
