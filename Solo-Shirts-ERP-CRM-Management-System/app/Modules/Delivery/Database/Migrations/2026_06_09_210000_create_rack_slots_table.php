<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A finite set of rack slots per branch. One slot holds at most one item at a
 * time. Single-occupancy per item is enforced at the DB: UNIQUE(branch_id,
 * current_order_item_id) — MySQL allows multiple NULLs, so it behaves as a
 * partial unique over occupied slots only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rack_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('slot_code');
            $table->string('label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('current_order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->timestamp('occupied_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'slot_code']);
            // One item can occupy at most one slot (NULLs allowed → partial unique).
            $table->unique(['branch_id', 'current_order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rack_slots');
    }
};
