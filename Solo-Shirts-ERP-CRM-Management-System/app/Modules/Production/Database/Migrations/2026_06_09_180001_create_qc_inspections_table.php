<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One QC inspection of an order item. attempt_number increments per item; a
 * rework disposition chains the next attempt via previous_inspection_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->foreignId('previous_inspection_id')->nullable()->constrained('qc_inspections')->nullOnDelete();
            $table->enum('disposition', ['pass', 'pass_with_note', 'rework', 'reject']);
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('inspected_at');
            $table->timestamps();

            $table->index(['order_item_id', 'attempt_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_inspections');
    }
};
