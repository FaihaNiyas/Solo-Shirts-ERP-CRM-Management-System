<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gap-free invoice number counter, one row per (branch, fiscal year). The number
 * service takes a row lock here so concurrent invoice creates serialize and
 * never share or skip a number. The Indian fiscal year (Apr 1) gives each branch
 * a fresh sequence every April.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id');
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedBigInteger('last_number')->default(0);

            $table->primary(['branch_id', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_sequences');
    }
};
