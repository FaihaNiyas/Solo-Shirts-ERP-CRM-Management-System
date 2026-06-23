<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QR removal — Phase 3 (final). The client no longer needs QR/scan/barcode, and
 * generation already stopped in Phase 2, so the leftover qr_payload columns and
 * their unique indexes are dropped permanently. Any existing qr_payload data is
 * intentionally discarded — it is a signed scan token with no business meaning
 * once QR is gone (the readable business codes customer_code / bundle_code /
 * roll_code are NOT touched).
 *
 * down() recreates the columns as nullable + unique for schema reversibility, but
 * the discarded token data is NOT restored (it is non-recoverable and unneeded).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_qr_payload_unique');
            $table->dropColumn('qr_payload');
        });

        Schema::table('cut_bundles', function (Blueprint $table) {
            $table->dropUnique('cut_bundles_qr_payload_unique');
            $table->dropColumn('qr_payload');
        });

        Schema::table('fabric_rolls', function (Blueprint $table) {
            $table->dropUnique('fabric_rolls_qr_payload_unique');
            $table->dropColumn('qr_payload');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('qr_payload')->nullable()->unique();
        });

        Schema::table('cut_bundles', function (Blueprint $table) {
            $table->string('qr_payload', 255)->nullable()->unique();
        });

        Schema::table('fabric_rolls', function (Blueprint $table) {
            $table->string('qr_payload')->nullable()->unique();
        });
    }
};
