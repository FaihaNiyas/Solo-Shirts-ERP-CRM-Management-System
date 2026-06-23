<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Production issues (Kanban Phase B). A parallel record of a problem raised against
 * an item at a given stage — distinct from the QC inspection trail and from cloth
 * damage. An issue is text-only (no photo). Open issues drive the card "issue
 * count" badge; resolving one closes it. Reporting an issue does NOT move the
 * item's production state — that stays owned by the state machine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('stage', 40);
            $table->string('issue_type', 40);
            $table->text('description');
            $table->string('status', 20)->default('open');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['order_item_id', 'status']);
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_issues');
    }
};
