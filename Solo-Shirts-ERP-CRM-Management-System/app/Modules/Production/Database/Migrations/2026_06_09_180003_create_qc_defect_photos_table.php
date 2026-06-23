<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Defect photos are uploaded standalone (pre-inspection) and linked to a defect
 * when the inspection is recorded, so qc_defect_id is nullable. The raw path is
 * never exposed — retrieval is via a temporary signed route only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_defect_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qc_defect_id')->nullable()->constrained('qc_defects')->nullOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->string('thumb_path')->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('qc_defect_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_defect_photos');
    }
};
