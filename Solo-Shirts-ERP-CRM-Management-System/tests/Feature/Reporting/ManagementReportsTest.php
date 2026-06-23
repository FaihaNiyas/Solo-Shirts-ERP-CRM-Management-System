<?php

declare(strict_types=1);

use App\Modules\Finance\Models\InvoiceLine;
use App\Modules\Finance\Models\Payment;
use App\Modules\Inventory\Contracts\StockLedgerInterface;
use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\Supplier;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->admin = makeUser($this->branch, 'Admin');     // holds reports.view (ALL_REPORTING)
    $this->fd = makeUser($this->branch, 'Front Desk');   // no reports.view
});

it('blocks Front Desk from every management report (403)', function () {
    foreach ([
        '/api/v1/reports/dashboard',
        '/api/v1/reports/orders/daily',
        '/api/v1/reports/payments/pending',
        '/api/v1/reports/production/stages',
        '/api/v1/reports/damage',
        '/api/v1/reports/sales-gst',
        '/api/v1/reports/inventory/stock',
        '/api/v1/reports/purchases',
    ] as $url) {
        $this->withHeaders(bearer($this->fd))->getJson($url)->assertForbidden();
    }
});

it('lets an authorized role load the management dashboard', function () {
    $this->withHeaders(bearer($this->admin))->getJson('/api/v1/reports/dashboard')
        ->assertOk()
        ->assertJsonStructure(['data' => ['date_range', 'orders', 'payments', 'production', 'inventory', 'damage', 'purchases']]);
});

it('counts daily orders and items', function () {
    deliverableOrder($this->branch, 2);
    deliverableOrder($this->branch, 1);

    $res = $this->withHeaders(bearer($this->admin))->getJson('/api/v1/reports/orders/daily')->assertOk();

    $today = now()->toDateString();
    $row = collect($res->json('data.rows'))->firstWhere('date', $today);
    expect($row['orders_count'])->toBe(2)
        ->and($row['items_count'])->toBe(3);
});

it('computes pending payment balances from invoice minus payments', function () {
    $order = deliverableOrder($this->branch);
    $invoice = makeInvoice($this->branch, $order, ['total_paise' => 100000, 'status' => 'partially_paid', 'issued_at' => now()]);
    Payment::factory()->for($this->branch)->create(['invoice_id' => $invoice->id, 'amount_paise' => 30000]);

    $res = $this->withHeaders(bearer($this->admin))->getJson('/api/v1/reports/payments/pending')->assertOk();

    $row = collect($res->json('data.rows'))->firstWhere('invoice_no', $invoice->invoice_no);
    expect($row['invoice_total_paise'])->toBe(100000)
        ->and($row['paid_paise'])->toBe(30000)
        ->and($row['balance_paise'])->toBe(70000);
});

it('counts production items by stage', function () {
    productionItem($this->branch, 'cutting');
    productionItem($this->branch, 'cutting');
    productionItem($this->branch, 'qc');

    $res = $this->withHeaders(bearer($this->admin))->getJson('/api/v1/reports/production/stages')->assertOk();

    $byStage = collect($res->json('data.rows'))->keyBy('stage');
    expect($byStage['cutting']['count'])->toBe(2)
        ->and($byStage['qc']['count'])->toBe(1)
        ->and($byStage['tailoring']['count'])->toBe(0);
});

it('groups damage by status, stage and type', function () {
    DamageReport::factory()->for($this->branch)->create(['stage' => 'cutting', 'damage_type' => 'tear', 'status' => 'approved', 'quantity_lost_metres' => 2, 'reported_at' => now()]);
    DamageReport::factory()->for($this->branch)->create(['stage' => 'cutting', 'damage_type' => 'stain', 'status' => 'pending', 'quantity_lost_metres' => 1, 'reported_at' => now()]);

    $res = $this->withHeaders(bearer($this->admin))->getJson('/api/v1/reports/damage')->assertOk();

    expect($res->json('data.totals.count'))->toBe(2)
        ->and($res->json('data.totals.quantity'))->toBe('3.00')
        ->and(collect($res->json('data.by_stage'))->firstWhere('key', 'cutting')['count'])->toBe(2)
        ->and(collect($res->json('data.by_status'))->firstWhere('key', 'approved')['count'])->toBe(1);
});

it('summarises sales/GST from invoice source of truth', function () {
    $invoice = makeInvoice($this->branch, null, [
        'subtotal_paise' => 100000, 'cgst_paise' => 2500, 'sgst_paise' => 2500, 'igst_paise' => 0,
        'total_paise' => 105000, 'status' => 'issued', 'issued_at' => now(),
    ]);
    InvoiceLine::factory()->create(['invoice_id' => $invoice->id, 'gst_rate' => 5, 'taxable_paise' => 100000, 'tax_paise' => 5000]);

    $res = $this->withHeaders(bearer($this->admin))->getJson('/api/v1/reports/sales-gst')->assertOk();

    expect($res->json('data.invoice_count'))->toBe(1)
        ->and($res->json('data.taxable_paise'))->toBe(100000)
        ->and($res->json('data.cgst_paise'))->toBe(2500)
        ->and($res->json('data.sgst_paise'))->toBe(2500)
        ->and($res->json('data.total_paise'))->toBe(105000)
        ->and(collect($res->json('data.by_rate'))->firstWhere('gst_rate', 5)['tax_paise'])->toBe(5000);
});

it('summarises inventory stock from rolls and the ledger', function () {
    $ledger = app(StockLedgerInterface::class);
    $roll = ledgerRoll($this->branch, 100.0);
    $ledger->recordReserve($roll, 1, 30.0, null);
    $ledger->recordConsume($roll, 1, 10.0, null);
    $ledger->record($roll->id, FabricMovement::TYPE_DAMAGE_WRITEOFF, 5.0, 'torn', ['type' => 'manual'], null);

    $res = $this->withHeaders(bearer($this->admin))->getJson('/api/v1/reports/inventory/stock')->assertOk();

    expect($res->json('data.fabric_rolls_count'))->toBe(1)
        ->and($res->json('data.remaining_total'))->toBe('85.00')
        ->and($res->json('data.reserved_total'))->toBe('20.00')
        ->and($res->json('data.consumed_total'))->toBe('10.00')
        ->and($res->json('data.damaged_total'))->toBe('5.00')
        ->and($res->json('data.available_total'))->toBe('65.00');
});

it('summarises purchases by status and supplier', function () {
    $supplier = Supplier::factory()->for($this->branch)->create(['name' => 'Acme']);
    PurchaseOrder::factory()->for($this->branch)->create(['supplier_id' => $supplier->id, 'status' => 'placed', 'total_paise' => 50000]);
    PurchaseOrder::factory()->for($this->branch)->create(['supplier_id' => $supplier->id, 'status' => 'received', 'total_paise' => 30000]);

    $res = $this->withHeaders(bearer($this->admin))->getJson('/api/v1/reports/purchases')->assertOk();

    expect($res->json('data.purchase_orders_count'))->toBe(2)
        ->and($res->json('data.placed_count'))->toBe(1)
        ->and($res->json('data.received_count'))->toBe(1)
        ->and($res->json('data.purchase_total_paise'))->toBe(80000)
        ->and(collect($res->json('data.by_supplier'))->firstWhere('supplier', 'Acme')['total_paise'])->toBe(80000);
});

it('scopes reports to the user branch (excludes other branches)', function () {
    $other = makeBranch(['code' => 'OTHER']);
    deliverableOrder($other, 3); // belongs to another branch
    deliverableOrder($this->branch, 1);

    $res = $this->withHeaders(bearer($this->admin))->getJson('/api/v1/reports/dashboard')->assertOk();

    // Only the HQ order is counted.
    expect($res->json('data.orders.total_orders'))->toBe(1);
});

it('respects the date range filter', function () {
    $old = deliverableOrder($this->branch);
    $old->forceFill(['created_at' => now()->subDays(60)])->save();
    deliverableOrder($this->branch); // today

    // Default 30-day window excludes the 60-day-old order.
    $this->withHeaders(bearer($this->admin))->getJson('/api/v1/reports/dashboard')
        ->assertOk()->assertJsonPath('data.orders.total_orders', 1);

    // Widening the window includes it.
    $from = now()->subDays(90)->toDateString();
    $this->withHeaders(bearer($this->admin))->getJson("/api/v1/reports/dashboard?from={$from}")
        ->assertOk()->assertJsonPath('data.orders.total_orders', 2);
});

it('does not mutate any data when generating reports', function () {
    deliverableOrder($this->branch, 2);
    $before = Order::query()->count();

    $this->withHeaders(bearer($this->admin))->getJson('/api/v1/reports/dashboard')->assertOk();
    $this->withHeaders(bearer($this->admin))->getJson('/api/v1/reports/sales-gst')->assertOk();

    expect(Order::query()->count())->toBe($before);
});
