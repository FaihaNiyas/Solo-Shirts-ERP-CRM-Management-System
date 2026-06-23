<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photos are uploaded standalone (pre-create) and linked when the report is
 * created, so damage_report_id is nullable. The raw path is never exposed —
 * retrieval is via a temporary signed route only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damage_report_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('damage_report_id')->nullable()->constrained('damage_reports')->nullOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->string('thumb_path')->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('damage_report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damage_report_photos');
    }
};
