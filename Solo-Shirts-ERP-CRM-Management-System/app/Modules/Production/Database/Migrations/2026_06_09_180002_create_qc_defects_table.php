<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_defects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qc_inspection_id')->constrained('qc_inspections')->cascadeOnDelete();
            $table->foreignId('defect_category_id')->constrained('defect_categories');
            $table->enum('severity', ['minor', 'major', 'critical']);
            $table->string('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('defect_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_defects');
    }
};
