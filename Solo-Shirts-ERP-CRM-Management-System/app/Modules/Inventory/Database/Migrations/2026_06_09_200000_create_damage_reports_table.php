<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fabric damage reports. A report is pending until the Owner approves it (which
 * deducts stock via a damage_writeoff ledger movement) or rejects it (stock
 * untouched).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damage_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('fabric_roll_id')->constrained('fabric_rolls');
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('stage', ['receiving', 'cutting', 'tailoring', 'qc', 'ironing', 'packing']);
            $table->enum('damage_type', ['tear', 'stain', 'color_bleed', 'mis_cut', 'machine_oil', 'other']);
            $table->string('damage_type_other')->nullable();
            $table->decimal('quantity_lost_metres', 8, 2);
            $table->string('action_taken')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('approval_notes')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamp('reported_at')->useCurrent();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index('fabric_roll_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damage_reports');
    }
};
