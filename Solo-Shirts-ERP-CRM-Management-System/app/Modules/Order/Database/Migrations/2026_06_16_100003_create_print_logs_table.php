<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit of every job-card print / reprint, so a counter can prove a
 * sub-order's document was produced and re-produced.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            // dateTime (not timestamp) to avoid MySQL's implicit ON UPDATE CURRENT_TIMESTAMP.
            $table->dateTime('printed_at');
            $table->boolean('is_reprint')->default(false);
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_logs');
    }
};
