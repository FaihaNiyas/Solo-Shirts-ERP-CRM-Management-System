<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QA-001 fix. `delivery_otps.expires_at` was declared as a TIMESTAMP. As the
 * first TIMESTAMP NOT NULL column in the table, MySQL/MariaDB (with
 * explicit_defaults_for_timestamp = 0) silently attached
 * `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`. Any UPDATE to the
 * row — e.g. incrementing `attempts` on a wrong OTP — therefore reset the
 * expiry to "now", so the next verification returned OTP_EXPIRED instead of
 * OTP_INVALID / OTP_LOCKED.
 *
 * DATETIME carries no such implicit ON UPDATE behaviour. Existing rows keep
 * their stored expiry value (ALTER ... MODIFY preserves data). Only the raw
 * OTP hash is ever stored; this migration does not touch it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_otps', function (Blueprint $table) {
            $table->dateTime('expires_at')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_otps', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable(false)->change();
        });
    }
};
