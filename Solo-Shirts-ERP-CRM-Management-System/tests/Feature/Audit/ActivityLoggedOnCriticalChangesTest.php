<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Delivery\Models\DeliveryAttempt;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

it('writes an audit activity row for every critical model on create', function () {
    $customer = Customer::factory()->for($this->branch)->create();
    deliverableOrder($this->branch);                       // Order + OrderItem
    approvedVersionFor($this->branch, $customer);          // MeasurementVersion
    $roll = fabricRoll($this->branch, 20.0);               // FabricRoll
    $invoice = makeInvoice($this->branch);                 // Invoice
    Payment::factory()->for($this->branch)->create(['invoice_id' => $invoice->id]);
    DamageReport::factory()->for($this->branch)->create(['fabric_roll_id' => $roll->id]);
    $delivery = makeDelivery($this->branch);
    DeliveryAttempt::factory()->for($this->branch)->create(['delivery_id' => $delivery->id]);

    $audited = [
        Customer::class, Order::class, OrderItem::class, MeasurementVersion::class,
        FabricRoll::class, Invoice::class, Payment::class, DamageReport::class, DeliveryAttempt::class,
    ];

    foreach ($audited as $subjectType) {
        expect(
            Activity::query()->where('log_name', 'audit')->where('subject_type', $subjectType)->exists()
        )->toBeTrue("expected an audit activity for {$subjectType}");
    }
});
