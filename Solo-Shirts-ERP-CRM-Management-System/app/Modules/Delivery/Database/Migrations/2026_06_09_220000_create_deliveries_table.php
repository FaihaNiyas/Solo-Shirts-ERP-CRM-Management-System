<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One delivery per order. Tracks the mode (pickup/home/courier), the lifecycle
 * status, and the delivery charges that surface separately on the Phase 15
 * invoice. The address is snapshotted at creation so later customer edits do
 * not rewrite delivery history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->enum('mode', ['pickup', 'home_delivery', 'courier']);
            $table->text('address_snapshot')->nullable();
            $table->string('courier_partner')->nullable();
            $table->string('tracking_no')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', [
                'scheduled', 'dispatched', 'attempted', 'delivered', 'failed', 'cancelled',
            ])->default('scheduled');
            $table->integer('delivery_charges_paise')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
