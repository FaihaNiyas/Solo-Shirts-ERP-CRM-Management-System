<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only stock ledger. `metres` is stored as a positive magnitude; the
 * `type` determines its direction (receive/adjust_in add to remaining,
 * out/adjust_out/damage_writeoff subtract, reserve/release are soft holds that
 * only affect "available"). A BEFORE UPDATE trigger makes the table write-once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fabric_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fabric_roll_id')->constrained('fabric_rolls')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->enum('type', [
                'receive', 'reserve', 'release', 'out', 'adjust_in', 'adjust_out', 'damage_writeoff',
            ]);
            $table->decimal('metres', 8, 2);
            $table->string('reason')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['fabric_roll_id', 'occurred_at']);
            $table->index(['branch_id', 'occurred_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        DB::unprepared(
            'CREATE TRIGGER fabric_movements_no_update BEFORE UPDATE ON fabric_movements '
            . "FOR EACH ROW SIGNAL SQLSTATE '45000' "
            . "SET MESSAGE_TEXT = 'fabric_movements is append-only';"
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS fabric_movements_no_update;');
        Schema::dropIfExists('fabric_movements');
    }
};
