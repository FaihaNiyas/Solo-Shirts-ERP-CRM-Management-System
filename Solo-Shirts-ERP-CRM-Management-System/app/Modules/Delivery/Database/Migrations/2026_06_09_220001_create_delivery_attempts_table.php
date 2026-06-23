<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only log of failed/partial delivery attempts with structured reason
 * codes, so dispatch teams can see why a delivery has not completed yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('deliveries')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->timestamp('attempted_at');
            $table->foreignId('attempted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('reason_code', [
                'customer_unavailable', 'wrong_address', 'refused', 'payment_pending', 'other',
            ]);
            $table->string('reason_notes')->nullable();
            $table->timestamps();

            $table->index('delivery_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_attempts');
    }
};
