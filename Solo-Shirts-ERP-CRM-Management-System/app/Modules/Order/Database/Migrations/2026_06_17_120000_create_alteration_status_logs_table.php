<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit trail for customer alteration status transitions (Phase 5B).
 * Each row records one move through the alteration workflow (intake → approved →
 * in_alteration → ready → delivered, or → cancelled). It is read only through its
 * parent alteration_request, which is already branch-scoped, so no branch_id here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alteration_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alteration_request_id')->constrained('alteration_requests')->cascadeOnDelete();
            $table->string('previous_status', 20);
            $table->string('new_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            // Append-only: created_at only (nullable timestamp avoids an implicit
            // ON UPDATE CURRENT_TIMESTAMP). No updated_at — rows are never mutated.
            $table->timestamp('created_at')->nullable();

            $table->index('alteration_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alteration_status_logs');
    }
};
