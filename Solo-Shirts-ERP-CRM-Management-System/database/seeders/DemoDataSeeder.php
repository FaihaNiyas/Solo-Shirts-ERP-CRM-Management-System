<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\FamilyMember;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Models\RackAssignment;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Identity\Models\Branch;
use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Inventory\Models\FabricType;
use App\Modules\Inventory\Models\Supplier;
use App\Modules\Measurement\Models\MeasurementProfile;
use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Realistic demo dataset for local development, demos and Playwright E2E. Builds
 * customers, family members, versioned measurements, orders spread across every
 * production state, fabric rolls (incl. low-stock), suppliers, invoices with
 * partial/full/outstanding payments, deliveries in every status, rack
 * assignments and damage reports — all within the HQ branch.
 *
 * Idempotent: re-running detects the DEMO- customer code prefix and skips.
 * Never runs in production.
 */
final class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoDataSeeder skipped in production.');

            return;
        }

        $branch = Branch::query()->where('code', 'HQ')->first() ?? Branch::query()->firstOrFail();

        if (Customer::query()->where('customer_code', 'like', 'DEMO-%')->exists()) {
            $this->command?->info('Demo data already present — skipping.');

            return;
        }

        DB::transaction(function () use ($branch): void {
            $this->createStaff($branch);
            $suppliers = $this->createSuppliers($branch);
            $this->createFabricRolls($branch, $suppliers);
            $customers = $this->createCustomersWithMeasurements($branch);
            $this->createOrdersAndDownstream($branch, $customers);
            $this->createDamageReports($branch);
        });

        $this->command?->info('Demo data seeded: 20 customers, 30 orders, 10 rolls, 5 suppliers, deliveries, invoices, racks.');
    }

    /** Staff covering each production hand-off. Password for all: "password". */
    private function createStaff(Branch $branch): void
    {
        $staff = [
            ['Ava Admin', 'admin@soloshirts.test', 'Admin'],
            ['Frontline Desk', 'frontdesk@soloshirts.test', 'Front Desk'],
            ['Maya Cutter', 'cutter@soloshirts.test', 'Cutting Master'],
            ['Tariq Tailor', 'tailor1@soloshirts.test', 'Tailor'],
            ['Anil Tailor', 'tailor2@soloshirts.test', 'Tailor'],
            ['Sara Stitch', 'tailor3@soloshirts.test', 'Tailor'],
            ['Iqbal Iron', 'ironing@soloshirts.test', 'Ironing Master'],
            ['Quentin QC', 'qc@soloshirts.test', 'QC Supervisor'],
            ['Priya Supervisor', 'supervisor@soloshirts.test', 'Production Supervisor'],
            ['Inder Stock', 'inventory@soloshirts.test', 'Inventory Manager'],
            ['Asha Accounts', 'accountant@soloshirts.test', 'Accountant'],
            ['Dev Delivery', 'delivery@soloshirts.test', 'Delivery Staff'],
        ];

        $registrar = app(PermissionRegistrar::class);

        foreach ($staff as [$name, $email, $role]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'branch_id' => $branch->id,
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            $registrar->setPermissionsTeamId($branch->id);
            if (!$user->hasRole($role)) {
                $user->assignRole($role);
            }
        }
    }

    /**
     * @return Collection<int, Supplier>
     */
    private function createSuppliers(Branch $branch)
    {
        return Supplier::factory()->count(5)->create(['branch_id' => $branch->id]);
    }

    /**
     * 10 rolls; a handful deliberately below their type's low-stock threshold so
     * the low-stock alert has something to surface.
     *
     * @param  Collection<int, Supplier>  $suppliers
     */
    private function createFabricRolls(Branch $branch, $suppliers): void
    {
        $types = FabricType::query()->get();

        for ($i = 0; $i < 10; $i++) {
            $type = $types->random();
            $threshold = (float) ($type->low_stock_threshold_metres ?? 3);
            // Every 4th roll is low stock (below threshold).
            $remaining = $i % 4 === 0
                ? round($threshold * 0.4, 2)
                : (float) random_int(20, 90);

            FabricRoll::factory()->create([
                'branch_id' => $branch->id,
                'fabric_type_id' => $type->id,
                'supplier_id' => $suppliers->random()->id,
                'received_length_metres' => max($remaining, (float) random_int(40, 100)),
                'remaining_metres' => $remaining,
            ]);
        }
    }

    /**
     * 20 customers; the first 5 get family members. Each customer gets a Slim Fit
     * and a Loose Fit measurement profile with an approved version.
     *
     * @return list<array{customer: Customer, versions: list<int>}>
     */
    private function createCustomersWithMeasurements(Branch $branch): array
    {
        $out = [];

        for ($i = 1; $i <= 20; $i++) {
            $customer = Customer::factory()->create([
                'branch_id' => $branch->id,
                'customer_code' => sprintf('DEMO-CUST-%03d', $i),
            ]);

            if ($i <= 5) {
                FamilyMember::factory()->count(random_int(1, 3))->create([
                    'customer_id' => $customer->id,
                ]);
            }

            $versions = [];
            foreach ([['Slim Fit', ['chest' => 38, 'waist' => 32, 'shirt_length' => 28]],
                ['Loose Fit', ['chest' => 44, 'waist' => 40, 'shirt_length' => 31]]] as [$fit, $shirt]) {
                $profile = MeasurementProfile::factory()->create([
                    'branch_id' => $branch->id,
                    'customer_id' => $customer->id,
                    'name' => $fit,
                    'type' => 'shirt',
                    'is_default' => $fit === 'Slim Fit',
                ]);

                $version = MeasurementVersion::factory()->create([
                    'branch_id' => $branch->id,
                    'profile_id' => $profile->id,
                    'version_number' => 1,
                    'status' => MeasurementVersion::STATUS_APPROVED,
                    'shirt_data' => $shirt,
                ]);
                $versions[] = $version->id;
            }

            // One pending version on the first few customers, to populate the
            // measurement approval inbox.
            if ($i <= 3) {
                $pendingProfile = MeasurementProfile::factory()->create([
                    'branch_id' => $branch->id,
                    'customer_id' => $customer->id,
                    'name' => 'Wedding Sherwani',
                    'type' => 'both',
                ]);
                MeasurementVersion::factory()->pending()->create([
                    'branch_id' => $branch->id,
                    'profile_id' => $pendingProfile->id,
                    'version_number' => 1,
                    'shirt_data' => ['chest' => 42, 'waist' => 36, 'shirt_length' => 30],
                    'pant_data' => ['waist' => 36, 'inseam' => 40, 'length' => 42],
                ]);
            }

            $out[] = ['customer' => $customer, 'versions' => $versions];
        }

        return $out;
    }

    /**
     * 30 orders spread across every production state. Ready/delivered orders get
     * rack assignments, deliveries and invoices (with partial/full/no payment).
     *
     * @param  list<array{customer: Customer, versions: list<int>}>  $customers
     */
    private function createOrdersAndDownstream(Branch $branch, array $customers): void
    {
        $states = [
            OrderItem::STATE_DRAFT,
            OrderItem::STATE_FABRIC_ALLOCATED,
            OrderItem::STATE_CUTTING,
            OrderItem::STATE_TAILORING,
            OrderItem::STATE_KAJA_BUTTON,
            OrderItem::STATE_FINISHING,
            OrderItem::STATE_QC,
            OrderItem::STATE_REWORK,
            OrderItem::STATE_PACKING,
            OrderItem::STATE_READY_FOR_DELIVERY,
            OrderItem::STATE_DELIVERED,
            OrderItem::STATE_CANCELLED,
        ];

        $slots = RackSlot::factory()->count(12)->sequence(
            fn ($s) => ['slot_code' => sprintf('A-%02d', $s->index + 1)],
        )->create(['branch_id' => $branch->id, 'current_order_item_id' => null]);
        $freeSlots = $slots->values();
        $slotCursor = 0;

        for ($i = 1; $i <= 30; $i++) {
            $entry = $customers[($i - 1) % count($customers)];
            $customer = $entry['customer'];
            $versionId = $entry['versions'][array_rand($entry['versions'])];
            $state = $states[($i - 1) % count($states)];

            $order = Order::factory()->create([
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'order_code' => sprintf('DEMO-ORD-%03d', $i),
                'delivery_mode' => ['pickup', 'home', 'courier'][$i % 3],
                'delivery_charges_paise' => $i % 3 === 1 ? 5000 : 0,
                'expected_delivery_date' => now()->addDays(random_int(2, 14)),
            ]);

            $itemCount = random_int(1, 3);
            $items = collect();
            for ($j = 0; $j < $itemCount; $j++) {
                $items->push(OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'branch_id' => $branch->id,
                    'measurement_version_id' => $versionId,
                    'product_type' => ['shirt', 'pant', 'combo'][$j % 3],
                    'quantity' => random_int(1, 4),
                    'state' => $state,
                ]));
            }

            // Rack-assign the lead item of ready-for-delivery orders.
            if ($state === OrderItem::STATE_READY_FOR_DELIVERY && $slotCursor < $freeSlots->count()) {
                $slot = $freeSlots[$slotCursor++];
                $lead = $items->first();
                $slot->update(['current_order_item_id' => $lead->id, 'occupied_at' => now()]);
                RackAssignment::factory()->create([
                    'branch_id' => $branch->id,
                    'rack_slot_id' => $slot->id,
                    'order_item_id' => $lead->id,
                    'assigned_at' => now(),
                ]);
            }

            // Deliveries for ready/delivered orders, in varied statuses.
            if (in_array($state, [OrderItem::STATE_READY_FOR_DELIVERY, OrderItem::STATE_DELIVERED], true)) {
                $deliveryStatus = $state === OrderItem::STATE_DELIVERED
                    ? Delivery::STATUS_DELIVERED
                    : [Delivery::STATUS_SCHEDULED, Delivery::STATUS_DISPATCHED, Delivery::STATUS_FAILED][$i % 3];

                Delivery::factory()->create([
                    'branch_id' => $branch->id,
                    'order_id' => $order->id,
                    'mode' => Delivery::MODE_HOME,
                    'status' => $deliveryStatus,
                    'dispatched_at' => $deliveryStatus === Delivery::STATUS_SCHEDULED ? null : now(),
                    'delivery_charges_paise' => $order->delivery_charges_paise,
                ]);
            }

            // Invoices + payments for further-along orders.
            if (in_array($state, [OrderItem::STATE_PACKING, OrderItem::STATE_READY_FOR_DELIVERY, OrderItem::STATE_DELIVERED], true)) {
                $this->createInvoiceWithPayment($branch, $order, $customer->id, $i);
            }
        }
    }

    private function createInvoiceWithPayment(Branch $branch, Order $order, int $customerId, int $i): void
    {
        $subtotalPaise = random_int(8_000, 40_000) * 100; // ₹8,000–₹40,000 in paise
        $cgst = (int) round($subtotalPaise * 0.025);
        $sgst = $cgst;
        $total = $subtotalPaise + $cgst + $sgst + $order->delivery_charges_paise;

        // 1 in 3 fully paid, 1 in 3 partially paid, 1 in 3 outstanding. Status is
        // set at creation — invoices are immutable for financial columns, so we
        // never UPDATE them after the fact.
        $mode = $i % 3;
        $status = match ($mode) {
            0 => Invoice::STATUS_PAID,
            1 => Invoice::STATUS_PARTIALLY_PAID,
            default => Invoice::STATUS_ISSUED,
        };

        $invoice = Invoice::factory()->create([
            'branch_id' => $branch->id,
            'order_id' => $order->id,
            'customer_id' => $customerId,
            'invoice_no' => sprintf('DEMO/INV/%04d', $i),
            'subtotal_paise' => $subtotalPaise,
            'cgst_paise' => $cgst,
            'sgst_paise' => $sgst,
            'igst_paise' => 0,
            'delivery_charges_paise' => $order->delivery_charges_paise,
            'total_paise' => $total,
            'status' => $status,
        ]);

        if ($mode === 0) {
            Payment::factory()->create([
                'branch_id' => $branch->id,
                'invoice_id' => $invoice->id,
                'amount_paise' => $total,
                'idempotency_key' => (string) Str::uuid(),
            ]);
        } elseif ($mode === 1) {
            Payment::factory()->create([
                'branch_id' => $branch->id,
                'invoice_id' => $invoice->id,
                'amount_paise' => (int) round($total * 0.4),
                'idempotency_key' => (string) Str::uuid(),
            ]);
        }
    }

    private function createDamageReports(Branch $branch): void
    {
        $rolls = FabricRoll::query()->where('branch_id', $branch->id)->inRandomOrder()->take(4)->get();
        $reporter = User::query()->where('email', 'inventory@soloshirts.test')->first();

        foreach ($rolls as $roll) {
            DamageReport::factory()->create([
                'branch_id' => $branch->id,
                'fabric_roll_id' => $roll->id,
                'reported_by' => $reporter?->id,
                'status' => DamageReport::STATUS_PENDING,
            ]);
        }
    }
}
