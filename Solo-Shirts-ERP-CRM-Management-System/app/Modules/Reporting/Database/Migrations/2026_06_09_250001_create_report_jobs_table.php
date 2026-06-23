<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks an on-demand report from request to completion. The heavy work runs on
 * the queue (RunReportJob); the row records status (pending → running →
 * succeeded/failed) and links the produced document when done.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('kind');
            $table->json('params')->nullable();
            $table->enum('status', ['pending', 'running', 'succeeded', 'failed'])->default('pending');
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->text('error')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_jobs');
    }
};
