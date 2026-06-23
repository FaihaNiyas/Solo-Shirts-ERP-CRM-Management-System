<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "On Hold" overlay for a production item (Kanban Phase A foundation, used by
 * Phase B). Deliberately NOT a state-machine state: an item keeps its real
 * production state and carries a parallel hold flag, so putting work on hold never
 * disrupts the linear flow or the downstream listeners (rack, delivery). A null
 * on_hold_at means the item is not on hold.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->timestamp('on_hold_at')->nullable()->after('cancel_reason');
            $table->string('on_hold_reason', 500)->nullable()->after('on_hold_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['on_hold_at', 'on_hold_reason']);
        });
    }
};
