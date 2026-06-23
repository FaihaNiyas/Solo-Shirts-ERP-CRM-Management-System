<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('customer_code')->unique();
            $table->string('name');
            $table->text('phone');                       // encrypted at rest
            $table->string('phone_last4', 8)->nullable(); // plaintext, for search
            $table->text('address')->nullable();
            $table->unsignedBigInteger('preferred_fabric_id')->nullable();
            $table->text('special_notes')->nullable();
            $table->string('qr_payload')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'name']);
            $table->index(['branch_id', 'phone_last4']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
