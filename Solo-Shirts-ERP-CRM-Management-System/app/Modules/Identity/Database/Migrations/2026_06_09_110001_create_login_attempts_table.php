<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('success')->default(false);
            $table->timestamp('attempted_at');

            $table->index(['email', 'attempted_at']);
            $table->index(['ip', 'attempted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
