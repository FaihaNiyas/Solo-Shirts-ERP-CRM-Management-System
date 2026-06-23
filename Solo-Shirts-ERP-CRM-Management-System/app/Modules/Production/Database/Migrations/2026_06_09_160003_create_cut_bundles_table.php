<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A bundle of cut pieces produced when an item's cutting completes. Each bundle
 * carries a signed QR payload so a tailor can scan it to start work (Phase 9).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cut_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('fabric_roll_id')->constrained('fabric_rolls');
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('bundle_code')->unique();
            // Signed payload is derived from the unique bundle_code, so it is
            // itself unique; varchar keeps it indexable (TEXT cannot be).
            $table->string('qr_payload', 255)->unique();
            $table->unsignedInteger('pieces_count');
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('order_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cut_bundles');
    }
};
