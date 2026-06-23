<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('from_state', 40)->nullable();
            $table->string('to_state', 40);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key')->unique();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            // INSERT-only: no updated_at. created_at stamps the write.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_item_id', 'occurred_at']);
            $table->index(['branch_id', 'occurred_at']);
        });

        // This ledger is append-only at the database level: any UPDATE raises an
        // error so an audited transition can never be rewritten after the fact.
        DB::unprepared(
            'CREATE TRIGGER production_transitions_no_update BEFORE UPDATE ON production_transitions '
            . "FOR EACH ROW SIGNAL SQLSTATE '45000' "
            . "SET MESSAGE_TEXT = 'production_transitions is append-only';"
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS production_transitions_no_update;');
        Schema::dropIfExists('production_transitions');
    }
};
