<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A two-phase fabric hold for one order_item: reserved → consumed (or released).
 * The reserved_metres are subtracted from a roll's "available" but not from its
 * physical remaining_metres until consumed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fabric_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('fabric_roll_id')->constrained('fabric_rolls');
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->decimal('reserved_metres', 8, 2);
            $table->decimal('consumed_metres', 8, 2)->nullable();
            $table->enum('status', ['reserved', 'consumed', 'released'])->default('reserved');
            $table->timestamp('reserved_at');
            $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('consumed_at')->nullable();
            $table->foreignId('consumed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('release_reason')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index('order_item_id');
            $table->index(['fabric_roll_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fabric_allocations');
    }
};
