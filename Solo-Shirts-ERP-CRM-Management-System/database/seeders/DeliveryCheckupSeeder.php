<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Customer\Models\Customer;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Models\RackAssignment;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Identity\Models\Branch;
use App\Modules\Measurement\Models\MeasurementProfile;
use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Focused, idempotent demo data for checking the FINAL DELIVERY flow. Creates two
 * confirmed, FULLY-PAID orders whose shirts are ready_for_delivery and racked:
 *
 *   DEMO-DEL-ORD-001  pickup  → test counter handover (Ready Rack → Collect & Handover)
 *   DEMO-DEL-ORD-002  home    → test home delivery (Deliveries → Dispatch → Confirm OTP)
 *
 * Fully paid, so the balance gate never blocks dispatch/handover. Idempotent:
 * re-running skips if the DEMO-DEL customers already exist. Never runs in
 * production. All rows use the DEMO- prefix so solo:cleanup-dummy-data removes them.
 */
final class DeliveryCheckupSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DeliveryCheckupSeeder skipped in production.');

            return;
        }

        $branch = Branch::query()->where('code', 'HQ')->first() ?? Branch::query()->firstOrFail();

        if (Customer::query()->where('customer_code', 'like', 'DEMO-DEL-%')->exists()) {
            $this->command?->info('Delivery-checkup demo data already present — skipping.');

            return;
        }

        DB::transaction(function () use ($branch): void {
            $this->buildReadyOrder($branch, '001', 'pickup', withDelivery: false);
            $this->buildReadyOrder($branch, '002', 'home', withDelivery: true);
        });

        $this->command?->info('Seeded DEMO-DEL-ORD-001 (counter pickup) and DEMO-DEL-ORD-002 (home delivery) — both ready + fully paid.');
    }

    private function buildReadyOrder(Branch $branch, string $code, string $mode, bool $withDelivery): void
    {
        $customer = Customer::factory()->create([
            'branch_id' => $branch->id,
            'customer_code' => 'DEMO-DEL-' . $code,
            'name' => 'Delivery Test ' . $code,
        ]);

        $profile = MeasurementProfile::factory()->create([
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'name' => 'Slim Fit',
            'type' => 'shirt',
            'is_default' => true,
        ]);

        $version = MeasurementVersion::factory()->create([
            'branch_id' => $branch->id,
            'profile_id' => $profile->id,
            'version_number' => 1,
            'status' => MeasurementVersion::STATUS_APPROVED,
            'shirt_data' => ['chest' => 38, 'waist' => 32, 'shirt_length' => 28],
        ]);

        $order = Order::factory()->create([
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'order_code' => 'DEMO-DEL-ORD-' . $code,
            'delivery_mode' => $mode,
            'lifecycle_status' => Order::LIFECYCLE_ORDER_RECEIVED,
            'delivery_charges_paise' => 0,
            'expected_delivery_date' => now()->addDays(2),
        ]);

        // Two ready shirts, each racked into its own DEMO slot.
        for ($j = 1; $j <= 2; $j++) {
            $item = OrderItem::factory()->create([
                'order_id' => $order->id,
                'branch_id' => $branch->id,
                'measurement_version_id' => $version->id,
                'product_type' => 'shirt',
                'quantity' => 1,
                'state' => OrderItem::STATE_READY_FOR_DELIVERY,
            ]);

            $slot = RackSlot::factory()->create([
                'branch_id' => $branch->id,
                'slot_code' => 'DEMO-' . $code . '-' . $j,
                'is_active' => true,
                'current_order_item_id' => $item->id,
                'occupied_at' => now(),
            ]);

            RackAssignment::factory()->create([
                'branch_id' => $branch->id,
                'rack_slot_id' => $slot->id,
                'order_item_id' => $item->id,
                'assigned_at' => now(),
            ]);
        }

        // Fully-paid invoice (₹3,000) so the balance gate never blocks delivery.
        $total = 300_000;
        $invoice = Invoice::factory()->create([
            'branch_id' => $branch->id,
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'DEMO/DEL/' . $code,
            'subtotal_paise' => $total,
            'cgst_paise' => 0,
            'sgst_paise' => 0,
            'igst_paise' => 0,
            'delivery_charges_paise' => 0,
            'total_paise' => $total,
            'status' => Invoice::STATUS_PAID,
        ]);

        Payment::factory()->create([
            'branch_id' => $branch->id,
            'invoice_id' => $invoice->id,
            'amount_paise' => $total,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        if ($withDelivery) {
            Delivery::factory()->create([
                'branch_id' => $branch->id,
                'order_id' => $order->id,
                'mode' => Delivery::MODE_HOME,
                'status' => Delivery::STATUS_SCHEDULED,
                'delivery_charges_paise' => 0,
            ]);
        }
    }
}
