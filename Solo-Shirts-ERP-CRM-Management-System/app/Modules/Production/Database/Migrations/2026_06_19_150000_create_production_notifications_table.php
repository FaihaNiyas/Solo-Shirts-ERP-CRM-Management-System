<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user in-app production notifications (Kanban Phase F). Distinct from the
 * outbound channel ledger (notifications table, whatsapp/email/sms): this is the
 * in-system feed a supervisor sees when an item enters their section, an issue is
 * raised, QC fails, an item is delayed, or it's ready for delivery. WhatsApp/email
 * can layer on later via the existing NotificationService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->string('type', 40);
            $table->string('title', 200);
            $table->string('body', 500)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'read_at']);
            $table->index(['branch_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_notifications');
    }
};
