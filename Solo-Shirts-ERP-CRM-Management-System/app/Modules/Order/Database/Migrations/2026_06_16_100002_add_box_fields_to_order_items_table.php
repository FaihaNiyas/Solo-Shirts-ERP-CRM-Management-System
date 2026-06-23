<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Front Desk Phase 2: one sub-order = one production box + one printed job card
 * placed inside it. These columns snapshot the assigned box and the placement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('production_box_id')->nullable()->after('design_notes')
                ->constrained('production_boxes')->nullOnDelete();
            // Human-readable code snapshot (survives even if the box is later freed).
            $table->string('box_code')->nullable()->after('production_box_id');
            $table->boolean('placed_in_box')->default(false)->after('box_code');
            $table->timestamp('placed_in_box_at')->nullable()->after('placed_in_box');
            $table->foreignId('placed_in_box_by')->nullable()->after('placed_in_box_at')
                ->constrained('users')->nullOnDelete();

            // One item holds at most one box (NULLs allowed for unassigned items).
            $table->unique('production_box_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropUnique(['production_box_id']);
            $table->dropConstrainedForeignId('production_box_id');
            $table->dropConstrainedForeignId('placed_in_box_by');
            $table->dropColumn(['box_code', 'placed_in_box', 'placed_in_box_at']);
        });
    }
};
