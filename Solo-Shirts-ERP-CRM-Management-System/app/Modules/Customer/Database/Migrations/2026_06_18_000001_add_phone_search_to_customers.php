<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Plaintext, digits-only phone used purely for progressive search
            // (the canonical `phone` column stays encrypted at rest).
            $table->string('phone_search', 20)->nullable()->after('phone_last4');
            $table->index(['branch_id', 'phone_search']);
        });

        // Backfill existing rows by decrypting `phone` and stripping non-digits.
        Customer::query()
            ->withoutGlobalScopes()
            ->withTrashed()
            ->chunkById(200, function ($customers): void {
                foreach ($customers as $customer) {
                    $digits = preg_replace('/\D/', '', (string) $customer->phone) ?? '';
                    $customer->forceFill(['phone_search' => $digits])->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'phone_search']);
            $table->dropColumn('phone_search');
        });
    }
};
