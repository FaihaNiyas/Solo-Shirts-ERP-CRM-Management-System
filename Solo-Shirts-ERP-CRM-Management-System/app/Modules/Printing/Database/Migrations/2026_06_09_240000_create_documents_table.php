<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rendered PDF documents. Files are content-addressed: the same input always
 * hashes to the same content_hash, and the UNIQUE (kind, reference, hash) key
 * makes a re-render reuse the existing row/file instead of duplicating it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->enum('kind', [
                'job_card', 'measurement_card', 'gst_invoice', 'packing_slip', 'delivery_receipt',
            ]);
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->string('disk');
            $table->string('path');
            $table->char('content_hash', 64);
            $table->unsignedBigInteger('size_bytes');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['kind', 'reference_type', 'reference_id', 'content_hash'], 'documents_dedupe_unique');
            $table->index(['reference_type', 'reference_id']);
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
