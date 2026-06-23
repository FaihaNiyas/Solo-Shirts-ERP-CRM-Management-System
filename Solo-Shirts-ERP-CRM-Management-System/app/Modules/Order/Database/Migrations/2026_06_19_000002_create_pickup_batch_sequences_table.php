<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gap-free pickup-batch and receipt number counters, one row per (branch, fiscal
 * year), mirroring invoice_sequences. PickupNumberService row-locks here so
 * concurrent batches/receipts never share or skip a number.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_batch_sequences', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id');
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedBigInteger('last_number')->default(0);

            $table->primary(['branch_id', 'fiscal_year']);
        });

        Schema::create('pickup_receipt_sequences', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id');
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedBigInteger('last_number')->default(0);

            $table->primary(['branch_id', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_batch_sequences');
        Schema::dropIfExists('pickup_receipt_sequences');
    }
};
