<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Richer "complete stage" capture on the append-only transition ledger (Kanban
 * Phase A): how many pieces were completed vs rejected at a stage move, and an
 * optional attachment (photo/file) reference. All nullable so historic rows and
 * automated transitions (QC, packing) remain valid.
 *
 * ADD COLUMN is DDL and is unaffected by the BEFORE UPDATE append-only trigger,
 * which only guards row UPDATEs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_transitions', function (Blueprint $table) {
            $table->unsignedInteger('completed_qty')->nullable()->after('notes');
            $table->unsignedInteger('rejected_qty')->nullable()->after('completed_qty');
            $table->string('attachment_path')->nullable()->after('rejected_qty');
        });
    }

    public function down(): void
    {
        Schema::table('production_transitions', function (Blueprint $table) {
            $table->dropColumn(['completed_qty', 'rejected_qty', 'attachment_path']);
        });
    }
};
