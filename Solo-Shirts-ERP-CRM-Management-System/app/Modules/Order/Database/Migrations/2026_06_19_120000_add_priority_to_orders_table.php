<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Production priority for an order (Kanban Phase A). Drives card sorting/badges on
 * the production board. Order-level by design — every garment on the order inherits
 * the order's urgency. Defaults to 'normal' so existing orders are unaffected.
 *
 * A legacy per-item hint lived in order_items.design_notes->priority ('rush'); the
 * production resource still reads it as a fallback so old data keeps rendering, but
 * this column is the authoritative source going forward.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('priority', 12)->default('normal')->after('lifecycle_status');
            $table->index(['branch_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'priority']);
            $table->dropColumn('priority');
        });
    }
};
