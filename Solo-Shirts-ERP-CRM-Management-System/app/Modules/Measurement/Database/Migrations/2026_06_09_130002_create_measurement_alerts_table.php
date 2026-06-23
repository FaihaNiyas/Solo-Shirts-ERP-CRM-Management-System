<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('version_id')->constrained('measurement_versions')->cascadeOnDelete();
            $table->json('fields_changed');
            $table->json('threshold_breached');
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('version_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_alerts');
    }
};
