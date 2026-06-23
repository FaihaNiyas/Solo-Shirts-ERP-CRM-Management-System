<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_sequences', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id');
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedBigInteger('last_number')->default(0);

            $table->primary(['branch_id', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_sequences');
    }
};
