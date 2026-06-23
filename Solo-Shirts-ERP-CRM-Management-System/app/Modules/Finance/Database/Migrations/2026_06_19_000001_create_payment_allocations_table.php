<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Item-level payment attribution ledger (Phase 1). A row says "this much of this
 * payment is attributed to this invoice line / order item". The invoice + payments
 * tables remain the financial source of truth; allocations are a derived,
 * append-only ledger that makes per-item balances possible without changing
 * existing money records.
 *
 * Invariants (enforced in PaymentAllocationService, not the DB):
 *   - sum(allocations for a payment)  == payment.amount_paise   (fully allocated)
 *   - sum(allocations for an item)    <= item invoice-line total (never over-paid)
 *   - order balance == sum(item balances)
 *
 * Append-only at the DB level (BEFORE UPDATE/DELETE triggers), mirroring payments.
 * Every FK is RESTRICT (no cascade / nullOnDelete) on purpose: an FK action that
 * UPDATEs or DELETEs an allocation row would fight the append-only triggers, so we
 * forbid deleting a referenced parent while an allocation exists instead.
 * pickup_batch_id is a plain nullable column (no FK) because pickup_batches is
 * created in the Phase 2 migration that runs after this one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments');
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->foreignId('invoice_line_id')->nullable()->constrained('invoice_lines');
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('order_item_id')->nullable()->constrained('order_items');
            $table->unsignedBigInteger('pickup_batch_id')->nullable();
            $table->integer('amount_paise');
            $table->enum('allocation_type', [
                'advance',
                'selected_item_balance',
                'remaining_items_balance',
                'full_order_balance',
                'manual',
            ]);
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('payment_id');
            $table->index('invoice_id');
            $table->index('order_id');
            $table->index('order_item_id');
            $table->index('pickup_batch_id');
        });

        DB::unprepared(
            'CREATE TRIGGER payment_allocations_no_update BEFORE UPDATE ON payment_allocations '
            . "FOR EACH ROW SIGNAL SQLSTATE '45000' "
            . "SET MESSAGE_TEXT = 'payment_allocations is append-only';"
        );

        DB::unprepared(
            'CREATE TRIGGER payment_allocations_no_delete BEFORE DELETE ON payment_allocations '
            . "FOR EACH ROW SIGNAL SQLSTATE '45000' "
            . "SET MESSAGE_TEXT = 'payment_allocations is append-only';"
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS payment_allocations_no_update;');
        DB::unprepared('DROP TRIGGER IF EXISTS payment_allocations_no_delete;');
        Schema::dropIfExists('payment_allocations');
    }
};
