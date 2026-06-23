<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Finance\Models\Payment;
use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Production\Models\ProductionTransition;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

it('forbids UPDATE on every append-only table at the database level', function () {
    // activity_log — created as a side effect of an audited model.
    Customer::factory()->for($this->branch)->create();
    $activityId = DB::table('activity_log')->value('id');

    $payment = Payment::factory()->for($this->branch)->create();
    $transition = ProductionTransition::factory()->create();
    $roll = ledgerRoll($this->branch, 10.0);
    $movementId = FabricMovement::query()->where('fabric_roll_id', $roll->id)->value('id');

    expect(fn () => DB::table('activity_log')->where('id', $activityId)->update(['description' => 'tampered']))
        ->toThrow(QueryException::class);

    expect(fn () => DB::table('payments')->where('id', $payment->id)->update(['amount_paise' => 1]))
        ->toThrow(QueryException::class);

    expect(fn () => DB::table('production_transitions')->where('id', $transition->id)->update(['to_state' => 'x']))
        ->toThrow(QueryException::class);

    expect(fn () => DB::table('fabric_movements')->where('id', $movementId)->update(['metres' => 0]))
        ->toThrow(QueryException::class);
});

it('forbids DELETE on the payment ledger at the database level', function () {
    $payment = Payment::factory()->for($this->branch)->create();

    expect(fn () => DB::table('payments')->where('id', $payment->id)->delete())
        ->toThrow(QueryException::class);
});
