<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assignment of a cut bundle to a tailor. At most one *active* assignment may
 * exist per bundle. MySQL has no partial unique index, so we emulate it with a
 * stored generated column that holds the bundle_id only while the assignment is
 * active (assigned/in_progress/completed) and NULL otherwise — a plain UNIQUE
 * index on it then ignores the NULLs (reassigned rows).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tailor_assignments', function (Blueprint $table) {
            $table->id();
            // No cascade: bundle_id is the base of the active_bundle_id generated
            // column, and MySQL forbids cascading referential actions on such a
            // column. RESTRICT is correct anyway — never delete an assigned bundle.
            $table->foreignId('bundle_id')->constrained('cut_bundles');
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('tailor_id')->constrained('users');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['assigned', 'in_progress', 'completed', 'reassigned'])->default('assigned');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unsignedBigInteger('active_bundle_id')->nullable()->storedAs(
                "(CASE WHEN status IN ('assigned','in_progress','completed') THEN bundle_id ELSE NULL END)"
            );

            $table->index(['tailor_id', 'completed_at']);
            $table->index(['branch_id', 'status']);
            $table->unique('active_bundle_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tailor_assignments');
    }
};
