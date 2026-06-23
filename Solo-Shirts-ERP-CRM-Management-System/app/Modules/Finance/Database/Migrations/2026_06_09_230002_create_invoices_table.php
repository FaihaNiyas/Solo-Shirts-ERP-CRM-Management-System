<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GST invoices. Financially append-only: a BEFORE UPDATE trigger forbids
 * rewriting the immutable identity/money columns (invoice_no, order_id and the
 * computed totals). Only mutable operational fields — status and pdf_path — may
 * change after issue; corrections are made through credit_notes, never edits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('invoice_no')->unique();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('customer_id')->constrained('customers');
            $table->enum('gst_treatment', ['regular', 'composition', 'unregistered']);
            $table->integer('subtotal_paise');
            $table->integer('cgst_paise')->default(0);
            $table->integer('sgst_paise')->default(0);
            $table->integer('igst_paise')->default(0);
            $table->integer('delivery_charges_paise')->default(0);
            $table->integer('discount_paise')->default(0);
            $table->integer('total_paise');
            $table->timestamp('issued_at');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['issued', 'partially_paid', 'paid', 'credited'])->default('issued');
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'issued_at']);
        });

        // Immutable identity/money columns. Status and pdf_path stay editable so
        // the payment reconciler and the Phase 16 PDF attach can do their work.
        DB::unprepared(
            'CREATE TRIGGER invoices_guard_immutable BEFORE UPDATE ON invoices '
            . 'FOR EACH ROW BEGIN '
            . 'IF NEW.invoice_no <> OLD.invoice_no '
            . 'OR NEW.order_id <> OLD.order_id '
            . 'OR NEW.subtotal_paise <> OLD.subtotal_paise '
            . 'OR NEW.cgst_paise <> OLD.cgst_paise '
            . 'OR NEW.sgst_paise <> OLD.sgst_paise '
            . 'OR NEW.igst_paise <> OLD.igst_paise '
            . 'OR NEW.total_paise <> OLD.total_paise THEN '
            . "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'invoice identity and totals are immutable'; "
            . 'END IF; END;'
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS invoices_guard_immutable;');
        Schema::dropIfExists('invoices');
    }
};
