<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Credit notes correct an issued invoice (invoices themselves are never edited).
 * Numbered gap-free per (branch, fiscal year) and append-only at the DB level.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('credit_no')->unique();
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->string('reason');
            $table->integer('total_paise');
            $table->timestamp('issued_at');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('invoice_id');
        });

        DB::unprepared(
            'CREATE TRIGGER credit_notes_no_update BEFORE UPDATE ON credit_notes '
            . "FOR EACH ROW SIGNAL SQLSTATE '45000' "
            . "SET MESSAGE_TEXT = 'credit_notes is append-only';"
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS credit_notes_no_update;');
        Schema::dropIfExists('credit_notes');
    }
};
