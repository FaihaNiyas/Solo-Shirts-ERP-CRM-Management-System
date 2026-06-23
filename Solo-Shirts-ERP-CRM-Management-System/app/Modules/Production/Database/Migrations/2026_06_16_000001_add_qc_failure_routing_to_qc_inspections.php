<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7C — capture WHY a QC inspection failed and WHERE the item should be
 * routed for internal production rework (distinct from a post-delivery customer
 * alteration). All nullable: a passing inspection leaves them null, and existing
 * rows are unaffected. The `disposition` column remains the source of truth for
 * pass/fail; these add the failure detail the shop floor needs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qc_inspections', function (Blueprint $table) {
            $table->string('failure_reason', 40)->nullable()->after('disposition');
            $table->string('failure_stage', 40)->nullable()->after('failure_reason');
            $table->string('rework_target_stage', 40)->nullable()->after('failure_stage');
        });
    }

    public function down(): void
    {
        Schema::table('qc_inspections', function (Blueprint $table) {
            $table->dropColumn(['failure_reason', 'failure_stage', 'rework_target_stage']);
        });
    }
};
