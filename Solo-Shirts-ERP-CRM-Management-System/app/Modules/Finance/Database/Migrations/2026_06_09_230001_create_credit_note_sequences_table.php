<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Credit notes need their own gap-free per-(branch, fiscal year) sequence,
 * independent of invoice numbering. Same row-lock discipline as
 * invoice_sequences.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_note_sequences', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id');
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedBigInteger('last_number')->default(0);

            $table->primary(['branch_id', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_sequences');
    }
};
