<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One pickup/fulfillment batch = one customer collection event for a subset of an
 * order's ready items (Phase 2). The batch carries its own money figures (total /
 * paid / balance) and lifecycle; only its items are delivered on handover.
 *
 * V1 payment_mode is pay_now / already_paid only — deferred (give-now-pay-later)
 * is intentionally NOT a value here; it arrives in a future, permissioned phase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('batch_no')->unique();
            $table->enum('pickup_type', ['counter_pickup', 'home_delivery', 'courier'])->default('counter_pickup');
            $table->enum('payment_mode', ['pay_now', 'already_paid']);
            $table->enum('status', ['draft', 'payment_pending', 'paid', 'handed_over', 'cancelled'])->default('draft');
            $table->integer('total_paise')->default(0);
            $table->integer('paid_paise')->default(0);
            $table->integer('balance_paise')->default(0);
            $table->timestamp('handed_over_at')->nullable();
            $table->foreignId('handed_over_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receipt_no')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_batches');
    }
};
