<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Front Desk WhatsApp notification ledger. Separate from the Reporting
 * `notifications` table (which dedupes one message per business reference and
 * serves other channels); Front Desk needs multiple event types per order and an
 * explicit `simulated` status when no provider is wired.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_whatsapp_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20)->default('whatsapp');
            $table->string('event_type', 40);
            $table->string('recipient_phone', 32);
            $table->text('message_body');
            // queued / sent / failed / simulated
            $table->string('status', 20);
            $table->string('provider_message_id')->nullable();
            $table->string('error_message')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            // dateTime (not timestamp) to avoid MySQL's implicit ON UPDATE.
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_whatsapp_notifications');
    }
};
