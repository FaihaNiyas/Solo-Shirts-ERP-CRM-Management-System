<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reports are filed as documents too (report_jobs.document_id → documents), so
 * the documents.kind enum gains a 'report' value. Kept out of the user-facing
 * renderable kinds (Document::KINDS) — only the report runner writes it.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE documents MODIFY COLUMN kind ENUM('
            . "'job_card','measurement_card','gst_invoice','packing_slip','delivery_receipt','report'"
            . ') NOT NULL'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE documents MODIFY COLUMN kind ENUM('
            . "'job_card','measurement_card','gst_invoice','packing_slip','delivery_receipt'"
            . ') NOT NULL'
        );
    }
};
