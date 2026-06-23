<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('po_code')->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->enum('status', ['draft', 'placed', 'partial_received', 'received', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('total_paise')->default(0);
            $table->string('notes')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('fabric_type_id')->constrained('fabric_types');
            $table->string('colour')->nullable();
            $table->decimal('quantity_metres', 8, 2);
            $table->unsignedBigInteger('unit_price_paise');
            $table->decimal('received_metres', 8, 2)->default(0);
            $table->timestamps();

            $table->index('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
