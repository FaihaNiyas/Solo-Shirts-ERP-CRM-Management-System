<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Server-side Front Desk order drafts (Phase 6B). A draft is NOT a confirmed
 * order — it holds the full wizard state so a counter can pause and resume from
 * any device, and a user can keep several drafts at once. The optional order_id
 * links a draft to the intake_preparation order created at the Print Center.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('front_desk_order_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('family_member_id')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('title')->nullable();
            // active / paused / converted / discarded
            $table->string('status', 12)->default('active');
            $table->string('current_step', 20)->nullable();
            $table->unsignedInteger('completed_count')->default(0);
            $table->unsignedInteger('total_items')->default(0);
            $table->json('draft_payload');
            $table->dateTime('last_saved_at')->nullable();
            $table->dateTime('converted_at')->nullable();
            $table->dateTime('discarded_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('front_desk_order_drafts');
    }
};
