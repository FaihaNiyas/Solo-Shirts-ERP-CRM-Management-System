<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The shirts inside a pickup batch (Phase 2). Each row snapshots the item's money
 * picture at batch creation (total, already-paid, amount due) and accrues what was
 * paid in this batch. Only these rows are delivered on handover.
 *
 * An item may be in at most one active (non-cancelled) batch — enforced in
 * PickupBatchService under a row lock. A plain unique on order_item_id is not used
 * because a cancelled batch's item must be allowed to re-enter a new batch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_batch_id')->constrained('pickup_batches')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('invoice_line_id')->nullable()->constrained('invoice_lines')->nullOnDelete();
            $table->integer('item_total_paise');
            $table->integer('paid_before_paise');
            $table->integer('amount_due_paise');
            $table->integer('paid_in_batch_paise')->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('rack_slot_id')->nullable()->constrained('rack_slots')->nullOnDelete();
            $table->timestamps();

            $table->index('order_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_batch_items');
    }
};
