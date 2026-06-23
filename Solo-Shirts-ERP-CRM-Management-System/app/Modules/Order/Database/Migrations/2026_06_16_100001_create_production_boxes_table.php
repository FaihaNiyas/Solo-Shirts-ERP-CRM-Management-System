<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Production boxes hold cut material + the printed job card for ONE sub-order
 * during Front Desk intake. They are deliberately separate from delivery
 * `rack_slots` (which stage ready-for-pickup garments) — the two must never mix.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('box_code');
            // free / occupied / released
            $table->string('status', 20)->default('free');
            $table->foreignId('current_order_item_id')->nullable()
                ->constrained('order_items')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Box codes are unique within a branch.
            $table->unique(['branch_id', 'box_code']);
            // A box can hold at most one active sub-order (MySQL allows many NULLs).
            $table->unique('current_order_item_id');
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_boxes');
    }
};
