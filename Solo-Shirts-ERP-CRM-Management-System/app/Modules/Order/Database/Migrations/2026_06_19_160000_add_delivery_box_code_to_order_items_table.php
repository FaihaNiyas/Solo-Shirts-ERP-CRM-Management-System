<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The pickup box / shelf number where a finished garment is physically placed
 * when it is staged for delivery. Entered on the board at the final move and
 * searched at the Front Desk when the customer comes to collect. Free-text and
 * indexed so a box-number lookup is a direct hit; distinct from the production
 * box (used during stitching) and the auto-assigned ready rack slot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->string('delivery_box_code', 50)->nullable()->after('placed_in_box_by');
            $table->index('delivery_box_code');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropIndex(['delivery_box_code']);
            $table->dropColumn('delivery_box_code');
        });
    }
};
