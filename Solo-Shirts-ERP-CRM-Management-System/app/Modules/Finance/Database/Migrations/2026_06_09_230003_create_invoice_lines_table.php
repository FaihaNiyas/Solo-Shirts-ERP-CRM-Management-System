<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-item invoice lines. Each carries its own taxable amount, GST rate and
 * computed tax so the invoice total is fully reproducible from its lines.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->string('description');
            $table->string('hsn_code')->nullable();
            $table->unsignedInteger('quantity');
            $table->integer('unit_price_paise');
            $table->integer('taxable_paise');
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->integer('tax_paise')->default(0);
            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
