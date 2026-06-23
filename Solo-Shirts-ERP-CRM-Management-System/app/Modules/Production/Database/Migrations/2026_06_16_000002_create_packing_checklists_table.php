<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7D — the final packing checklist for one sub-order (order item). One row
 * per item (upserted as the floor ticks boxes); packed_by/packed_at are stamped
 * only when the item is marked packed and promoted to ready-for-delivery. This is
 * a production artefact — it never touches invoices, payments or the rack ledger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packing_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')->unique()->constrained('order_items')->cascadeOnDelete();
            $table->boolean('checked_measurement_card')->default(false);
            $table->boolean('checked_buttons')->default(false);
            $table->boolean('checked_ironing')->default(false);
            $table->boolean('checked_folded')->default(false);
            $table->boolean('checked_packing_cover')->default(false);
            $table->boolean('checked_label')->default(false);
            $table->foreignId('packed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('packed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packing_checklists');
    }
};
