<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * QR removal — Phase 2. The QR feature is being retired, so the system stops
 * generating qr_payload. These two columns were NOT NULL, so they must become
 * nullable before generation stops (otherwise new customer/bundle inserts would
 * fail). The UNIQUE index stays — MySQL allows many NULLs under a unique index.
 *
 * The columns themselves are dropped later in Phase 3 (separate approval). The
 * down() restore to NOT NULL only succeeds while no NULL rows exist yet (i.e.
 * immediately after up()); once generation has stopped, roll forward to Phase 3
 * instead of rolling back.
 *
 * fabric_rolls.qr_payload is already nullable, so it needs no change here.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE customers MODIFY qr_payload VARCHAR(255) NULL');
        DB::statement('ALTER TABLE cut_bundles MODIFY qr_payload VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE customers MODIFY qr_payload VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE cut_bundles MODIFY qr_payload VARCHAR(255) NOT NULL');
    }
};
