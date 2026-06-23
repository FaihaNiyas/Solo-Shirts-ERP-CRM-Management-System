<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only payment ledger. The unique idempotency_key makes record() safe to
 * retry. INSERT-only is enforced at the DB level (BEFORE UPDATE/DELETE triggers
 * raise an error), mirroring the README's "payments are insert-only" grant. UPI
 * IDs are encrypted at rest; only the last 4 of a bank account are kept (plain).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->enum('method', ['cash', 'upi', 'bank_transfer']);
            $table->integer('amount_paise');
            $table->string('reference_no')->nullable();
            $table->timestamp('paid_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('upi_id')->nullable();
            $table->string('bank_account_last4', 4)->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamp('created_at')->useCurrent();

            $table->index('invoice_id');
        });

        DB::unprepared(
            'CREATE TRIGGER payments_no_update BEFORE UPDATE ON payments '
            . "FOR EACH ROW SIGNAL SQLSTATE '45000' "
            . "SET MESSAGE_TEXT = 'payments is append-only';"
        );

        DB::unprepared(
            'CREATE TRIGGER payments_no_delete BEFORE DELETE ON payments '
            . "FOR EACH ROW SIGNAL SQLSTATE '45000' "
            . "SET MESSAGE_TEXT = 'payments is append-only';"
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS payments_no_update;');
        DB::unprepared('DROP TRIGGER IF EXISTS payments_no_delete;');
        Schema::dropIfExists('payments');
    }
};
