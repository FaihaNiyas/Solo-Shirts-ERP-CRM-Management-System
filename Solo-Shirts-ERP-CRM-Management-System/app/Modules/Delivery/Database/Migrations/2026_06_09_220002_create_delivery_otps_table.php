<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One-time delivery confirmation codes. Only the hash is ever stored; the raw
 * 6-digit OTP is transmitted exclusively via the notification channel. A code
 * expires after 10 minutes and locks after 5 failed verify attempts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('deliveries')->cascadeOnDelete();
            $table->string('otp_hash');
            $table->timestamp('expires_at');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['delivery_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_otps');
    }
};
