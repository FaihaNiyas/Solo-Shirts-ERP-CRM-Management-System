<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->text('phone')->nullable()->after('email');                 // encrypted at rest
            $table->text('two_factor_secret')->nullable()->after('password');  // encrypted at rest
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
            $table->boolean('is_active')->default(true)->after('two_factor_confirmed_at');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn([
                'phone',
                'two_factor_secret',
                'two_factor_confirmed_at',
                'is_active',
                'deleted_at',
            ]);
        });
    }
};
