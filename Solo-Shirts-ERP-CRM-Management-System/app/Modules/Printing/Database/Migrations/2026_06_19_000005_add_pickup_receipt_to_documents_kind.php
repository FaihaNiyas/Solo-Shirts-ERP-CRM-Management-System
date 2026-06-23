<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the `pickup_receipt` document kind (Phase 2) so a per-batch pickup slip can
 * be filed alongside job cards / packing slips. `report` is included because it is
 * already a live value used by report exports — keeping the full list here makes
 * dev and a freshly-migrated test DB agree.
 */
return new class extends Migration
{
    private const WITH_PICKUP = "ENUM('job_card','measurement_card','gst_invoice','packing_slip','delivery_receipt','report','pickup_receipt')";

    private const WITHOUT_PICKUP = "ENUM('job_card','measurement_card','gst_invoice','packing_slip','delivery_receipt','report')";

    public function up(): void
    {
        DB::statement('ALTER TABLE documents MODIFY COLUMN kind ' . self::WITH_PICKUP . ' NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE documents MODIFY COLUMN kind ' . self::WITHOUT_PICKUP . ' NOT NULL');
    }
};
