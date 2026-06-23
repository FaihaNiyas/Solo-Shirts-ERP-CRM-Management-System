<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fabric roll stock. remaining_metres is a cache recomputed inside the same
 * transaction as every ledger movement; a CHECK constraint forbids it going
 * negative. Phase 8 reserves/consumes against these rolls; Phase 11 owns them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fabric_rolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('roll_code')->unique();
            $table->foreignId('fabric_type_id')->constrained('fabric_types');
            $table->string('colour')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('received_length_metres', 8, 2);
            $table->decimal('remaining_metres', 8, 2);
            $table->unsignedBigInteger('unit_price_paise')->nullable();
            $table->date('received_date')->nullable();
            $table->string('rack_location')->nullable();
            $table->string('qr_payload')->nullable()->unique();
            $table->enum('status', ['active', 'depleted', 'written_off'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index('fabric_type_id');
        });

        // Stock can never go negative.
        DB::statement('ALTER TABLE fabric_rolls ADD CONSTRAINT fabric_rolls_remaining_non_negative CHECK (remaining_metres >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('fabric_rolls');
    }
};
