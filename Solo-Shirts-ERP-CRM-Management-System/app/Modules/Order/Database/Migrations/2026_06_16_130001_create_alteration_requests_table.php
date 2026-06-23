<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer post-delivery alteration intake — a Front Desk record created when a
 * customer returns a delivered shirt for a fitting/stitching correction. This is
 * deliberately SEPARATE from internal QC rework (an order_item production state):
 * different actor (Front Desk vs QC), different time (after delivery vs before),
 * and a different queue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alteration_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('original_order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('issue_type', 40);
            $table->text('issue_description');
            $table->string('priority', 10)->default('normal'); // normal / urgent
            $table->boolean('charge_required')->default(false);
            $table->unsignedInteger('estimated_charge_paise')->nullable();
            // intake / approved / in_alteration / ready / delivered / cancelled
            $table->string('status', 20)->default('intake');
            $table->string('photo_path')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index('original_order_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alteration_requests');
    }
};
