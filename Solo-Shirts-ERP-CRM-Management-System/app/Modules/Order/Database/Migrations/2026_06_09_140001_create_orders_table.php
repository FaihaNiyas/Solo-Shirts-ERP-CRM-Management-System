<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('order_code')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('source', ['walk_in', 'phone', 'whatsapp', 'online']);
            $table->string('channel_notes')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->enum('delivery_mode', ['pickup', 'home', 'courier']);
            $table->unsignedInteger('delivery_charges_paise')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
