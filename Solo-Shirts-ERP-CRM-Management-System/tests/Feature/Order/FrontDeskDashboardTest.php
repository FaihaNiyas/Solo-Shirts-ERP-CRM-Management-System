<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Order\Models\AlterationRequest;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\WhatsappNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->fd = makeUser($this->branch, 'Front Desk');
});

function dashOrder($branch, string $lifecycle, ?string $deliveryDate, string $itemState): Order
{
    $customer = Customer::factory()->for($branch)->create();
    $order = Order::factory()->for($branch)->for($customer)->create([
        'lifecycle_status' => $lifecycle,
        'expected_delivery_date' => $deliveryDate,
    ]);
    OrderItem::factory()->for($branch)->for($order)->create(['state' => $itemState]);

    return $order;
}

function dashInvoice(Order $order, int $totalPaise, int $paidPaise): void
{
    $invoice = Invoice::factory()->for($order->branch)->create([
        'order_id' => $order->id,
        'customer_id' => $order->customer_id,
        'total_paise' => $totalPaise,
    ]);
    if ($paidPaise > 0) {
        Payment::factory()->for($order->branch)->create([
            'invoice_id' => $invoice->id,
            'amount_paise' => $paidPaise,
            'method' => 'cash',
            'paid_at' => now(),
        ]);
    }
}

it('returns branch-scoped operational counts for Front Desk', function () {
    // A: confirmed, due today, ready for pickup, balance 600 (1000 - 400 paid today).
    $a = dashOrder($this->branch, 'order_received', today()->toDateString(), OrderItem::STATE_READY_FOR_DELIVERY);
    dashInvoice($a, 100000, 40000);
    // B: intake preparation (not confirmed).
    dashOrder($this->branch, 'intake_preparation', today()->toDateString(), OrderItem::STATE_DRAFT);
    // C: confirmed, overdue (delivery yesterday), still in production.
    dashOrder($this->branch, 'order_received', today()->subDay()->toDateString(), OrderItem::STATE_TAILORING);

    // Active alteration + a simulated WhatsApp today.
    AlterationRequest::query()->create([
        'branch_id' => $this->branch->id, 'original_order_id' => $a->id,
        'original_order_item_id' => $a->items()->first()->id, 'customer_id' => $a->customer_id,
        'issue_type' => 'fitting_issue', 'issue_description' => 'x', 'priority' => 'normal', 'status' => 'intake',
    ]);
    WhatsappNotification::query()->create([
        'branch_id' => $this->branch->id, 'customer_id' => $a->customer_id, 'order_id' => $a->id,
        'channel' => 'whatsapp', 'event_type' => 'order_confirmed', 'recipient_phone' => '9876543210',
        'message_body' => 'hi', 'status' => 'simulated',
    ]);

    // Noise in another branch — must NOT affect HQ counts.
    $other = makeBranch(['code' => 'OTHER']);
    $ox = dashOrder($other, 'order_received', today()->toDateString(), OrderItem::STATE_READY_FOR_DELIVERY);
    dashInvoice($ox, 500000, 0);

    $this->withHeaders(bearer($this->fd))->getJson('/api/v1/front-desk/dashboard')
        ->assertOk()
        ->assertJsonPath('data.today.new_orders_count', 3)
        ->assertJsonPath('data.today.confirmed_orders_count', 2)
        ->assertJsonPath('data.today.intake_preparation_count', 1)
        ->assertJsonPath('data.today.due_today_count', 1)
        ->assertJsonPath('data.today.overdue_count', 1)
        ->assertJsonPath('data.pickup.ready_for_pickup_count', 1)
        ->assertJsonPath('data.pickup.ready_with_balance_pending_count', 1)
        ->assertJsonPath('data.pickup.ready_fully_paid_count', 0)
        ->assertJsonPath('data.payments.pending_balance_orders_count', 1)
        ->assertJsonPath('data.payments.pending_balance_amount', 600)
        ->assertJsonPath('data.payments.payments_collected_today', 400)
        ->assertJsonPath('data.alterations.active_count', 1)
        ->assertJsonPath('data.alterations.intake_count', 1)
        ->assertJsonPath('data.notifications.whatsapp_simulated_today_count', 1)
        ->assertJsonPath('data.notifications.whatsapp_failed_count', 0)
        ->assertJsonCount(1, 'data.quick_lists.due_today')
        ->assertJsonCount(1, 'data.quick_lists.ready_for_pickup')
        ->assertJsonCount(1, 'data.quick_lists.pending_balance')
        ->assertJsonCount(1, 'data.quick_lists.active_alterations');
});

it('shows the full phone to Front Desk in quick lists', function () {
    $a = dashOrder($this->branch, 'order_received', today()->toDateString(), OrderItem::STATE_READY_FOR_DELIVERY);
    dashInvoice($a, 100000, 0);
    $phone = $a->customer->phone;

    $this->withHeaders(bearer($this->fd))->getJson('/api/v1/front-desk/dashboard')
        ->assertOk()
        ->assertJsonPath('data.quick_lists.pending_balance.0.phone', $phone);
});

it('limits quick lists to at most ten rows', function () {
    for ($i = 0; $i < 13; $i++) {
        $o = dashOrder($this->branch, 'order_received', today()->toDateString(), OrderItem::STATE_TAILORING);
        dashInvoice($o, 100000, 0); // each has a 1000 balance
    }

    $data = $this->withHeaders(bearer($this->fd))->getJson('/api/v1/front-desk/dashboard')->assertOk()->json('data');
    expect(count($data['quick_lists']['pending_balance']))->toBeLessThanOrEqual(10)
        ->and(count($data['quick_lists']['due_today']))->toBeLessThanOrEqual(10)
        ->and($data['payments']['pending_balance_orders_count'])->toBe(13);
});

it('forbids the dashboard without orders.view (403)', function () {
    $staff = makeUser($this->branch, 'Measurement Staff'); // no orders.view

    $this->withHeaders(bearer($staff))->getJson('/api/v1/front-desk/dashboard')->assertStatus(403);
});

it('returns zeroed metrics when the branch has no activity', function () {
    $this->withHeaders(bearer($this->fd))->getJson('/api/v1/front-desk/dashboard')
        ->assertOk()
        ->assertJsonPath('data.today.new_orders_count', 0)
        ->assertJsonPath('data.payments.pending_balance_amount', 0)
        ->assertJsonPath('data.quick_lists.due_today', []);
});
