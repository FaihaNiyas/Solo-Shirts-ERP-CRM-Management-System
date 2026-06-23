<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maps a user to the production section (stage) they supervise within a branch
 * (Kanban Phase C). A stage may have several supervisors and a user may supervise
 * several stages. Drives the card "assigned supervisor" and the "my section" board
 * filter. Roles already gate WHAT a user may do; this records WHICH section they own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_stage_supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('stage', 40);
            $table->timestamps();

            $table->unique(['branch_id', 'user_id', 'stage']);
            $table->index(['branch_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_stage_supervisors');
    }
};
