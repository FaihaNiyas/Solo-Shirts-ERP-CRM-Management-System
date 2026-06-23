<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('item_code');
            $table->enum('product_type', ['shirt', 'pant', 'combo']);
            $table->unsignedInteger('quantity')->default(1);
            // Orders may only bind APPROVED measurement versions (enforced in code).
            $table->foreignId('measurement_version_id')->constrained('measurement_versions');
            $table->text('fabric_preference_text')->nullable();
            $table->json('design_notes')->nullable();
            // Production state machine formalized in Phase 7; plain string here.
            $table->string('state', 40)->default('draft');
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'state']);
            $table->index('order_id');
            $table->index('measurement_version_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
