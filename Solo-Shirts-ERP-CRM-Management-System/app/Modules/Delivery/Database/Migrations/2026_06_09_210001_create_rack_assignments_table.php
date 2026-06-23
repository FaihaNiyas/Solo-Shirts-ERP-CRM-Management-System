<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The slot occupancy ledger. At most one ACTIVE (released_at IS NULL) assignment
 * may exist per slot and per item. MySQL has no partial unique index, so we
 * emulate it with stored generated columns that hold the id only while active
 * and NULL otherwise — a plain UNIQUE then ignores the released rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rack_assignments', function (Blueprint $table) {
            $table->id();
            // No cascade: these columns feed the active_* generated columns, and
            // MySQL forbids cascading referential actions on such a column (1215).
            $table->foreignId('rack_slot_id')->constrained('rack_slots');
            $table->foreignId('order_item_id')->constrained('order_items');
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->timestamp('assigned_at');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('release_reason')->nullable();
            $table->timestamps();

            $table->unsignedBigInteger('active_slot_id')->nullable()->storedAs(
                '(CASE WHEN released_at IS NULL THEN rack_slot_id ELSE NULL END)'
            );
            $table->unsignedBigInteger('active_item_id')->nullable()->storedAs(
                '(CASE WHEN released_at IS NULL THEN order_item_id ELSE NULL END)'
            );

            $table->unique('active_slot_id');
            $table->unique('active_item_id');
            $table->index('order_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rack_assignments');
    }
};
