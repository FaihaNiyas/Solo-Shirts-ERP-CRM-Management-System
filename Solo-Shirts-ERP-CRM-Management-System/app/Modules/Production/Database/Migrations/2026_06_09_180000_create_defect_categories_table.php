<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Managed list of defect categories for QC analytics. Global (not branch-scoped)
 * so "most frequent defects" can roll up consistently across branches.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defect_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defect_categories');
    }
};
