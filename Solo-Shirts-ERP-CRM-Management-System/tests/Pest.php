<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Identity\Models\Branch;
use App\Modules\Inventory\Contracts\StockLedgerInterface;
use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Inventory\Models\FabricType;
use App\Modules\Measurement\Models\MeasurementProfile;
use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Models\CutBundle;
use App\Modules\Shared\Services\NotificationDispatcher;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\FakeNotificationDispatcher;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->in('Feature');

// Unit tests that touch the framework container (e.g. ApiResponse) need a booted app.
pest()->extend(TestCase::class)
    ->in('Unit/Shared');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function seedRoles(): void
{
    (new RolePermissionSeeder)->run();
}

/**
 * Build a valid create-order payload (one shirt bound to an approved version).
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function orderPayload(int $customerId, int $versionId, array $overrides = []): array
{
    return array_merge([
        'customer_id' => $customerId,
        'source' => 'walk_in',
        'delivery_mode' => 'pickup',
        'items' => [
            ['product_type' => 'shirt', 'quantity' => 1, 'measurement_version_id' => $versionId],
        ],
    ], $overrides);
}

/**
 * Build a valid create-invoice payload (regular GST, one line, small discount).
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function invoicePayload(int $orderId, array $overrides = []): array
{
    return array_merge([
        'order_id' => $orderId,
        'gst_treatment' => 'regular',
        'inter_state' => false,
        'discount_paise' => 5000,
        'lines' => [
            ['description' => 'Shirt stitching', 'hsn_code' => '9988', 'quantity' => 2, 'unit_price_paise' => 50000, 'gst_rate' => 5],
        ],
    ], $overrides);
}

function makeBranch(array $attrs = []): Branch
{
    return Branch::factory()->create($attrs);
}

/**
 * Create a user in a branch, optionally with a role (assigned under that
 * branch's team context).
 */
function makeUser(?Branch $branch = null, ?string $role = null, array $attrs = []): User
{
    $branch ??= makeBranch();

    /** @var User $user */
    $user = User::factory()->create(array_merge(['branch_id' => $branch->id], $attrs));

    if ($role !== null) {
        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);
        $user->assignRole($role);
    }

    return $user;
}

/**
 * @return array<string, string>
 */
function bearer(User $user): array
{
    $token = $user->createToken('test')->plainTextToken;

    // Drop any guard user cached by a prior request so this request's token is
    // resolved fresh (each real HTTP request is its own process).
    forgetAuth();

    return ['Authorization' => 'Bearer ' . $token];
}

/**
 * Drop the cached guard user so the next request in the same test re-resolves
 * authentication from its headers (each real HTTP request is a fresh process;
 * within one test the guard singleton would otherwise reuse the first user).
 */
function forgetAuth(): void
{
    app('auth')->forgetGuards();
}

/**
 * Create an approved measurement version for a customer in a branch.
 */
function approvedVersionFor(Branch $branch, Customer $customer): MeasurementVersion
{
    $profile = MeasurementProfile::factory()->for($branch)->for($customer)->create();

    return MeasurementVersion::factory()
        ->for($branch)
        ->for($profile, 'profile')
        ->create(['status' => MeasurementVersion::STATUS_APPROVED]);
}

/**
 * Create an order_item in a branch, sitting in a given production state.
 */
function productionItem(Branch $branch, string $state = OrderItem::STATE_DRAFT): OrderItem
{
    $customer = Customer::factory()->for($branch)->create();
    $order = Order::factory()->for($branch)->for($customer)->create();

    return OrderItem::factory()
        ->for($branch)
        ->for($order)
        ->create(['state' => $state]);
}

/**
 * Fire a production transition request as the given user. The endpoint requires
 * an Idempotency-Key; a fresh one is generated unless supplied.
 *
 * @param  array<string, mixed>  $body
 */
function transitionItem(TestCase $test, User $user, int $itemId, array $body, ?string $key = null): TestResponse
{
    $key ??= (string) Str::uuid();

    return $test->withHeaders(bearer($user) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/production/items/{$itemId}/transition", $body);
}

/**
 * Create an active fabric roll in a branch with a known remaining length.
 */
function fabricRoll(Branch $branch, float $metres): FabricRoll
{
    return FabricRoll::factory()->for($branch)->withRemaining($metres)->create();
}

/**
 * Create a ledger-backed roll: zero remaining seeded up to $metres via a real
 * receive movement, so the cached remaining and the ledger sum agree.
 */
function ledgerRoll(Branch $branch, float $metres, ?FabricType $type = null): FabricRoll
{
    $type ??= FabricType::factory()->create();

    $roll = FabricRoll::factory()->for($branch)->create([
        'fabric_type_id' => $type->id,
        'received_length_metres' => $metres,
        'remaining_metres' => 0,
    ]);

    app(StockLedgerInterface::class)->record(
        $roll->id,
        FabricMovement::TYPE_RECEIVE,
        $metres,
        'seed',
        [],
        null,
    );

    return $roll->refresh();
}

/**
 * Reserve fabric for an item via the API (the route is idempotent).
 *
 * @param  array<string, mixed>  $body
 */
function allocateFabric(TestCase $test, User $user, int $itemId, array $body, ?string $key = null): TestResponse
{
    $key ??= (string) Str::uuid();

    return $test->withHeaders(bearer($user) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/cutting/items/{$itemId}/allocate-fabric", $body);
}

/**
 * Create a rack slot in a branch.
 */
function rackSlot(Branch $branch, ?string $code = null, bool $active = true): RackSlot
{
    return RackSlot::factory()->for($branch)->create([
        'slot_code' => $code ?? 'R-' . strtoupper(Str::random(5)),
        'is_active' => $active,
    ]);
}

/**
 * Swap the NotificationDispatcher for an in-memory fake and return it, so tests
 * can read the raw OTP the real system would only send over a channel.
 */
function fakeNotifications(): FakeNotificationDispatcher
{
    $fake = new FakeNotificationDispatcher;
    app()->instance(NotificationDispatcher::class, $fake);

    return $fake;
}

/**
 * Create an order in a branch with $items order_items sitting in a given state
 * (defaults to ready_for_delivery, the precondition for a delivery confirm).
 */
function deliverableOrder(Branch $branch, int $items = 1, string $state = OrderItem::STATE_READY_FOR_DELIVERY): Order
{
    $customer = Customer::factory()->for($branch)->create();
    $order = Order::factory()->for($branch)->for($customer)->create();

    OrderItem::factory()->count($items)->for($branch)->for($order)->create(['state' => $state]);

    return $order;
}

/**
 * Create a delivery in a branch. Builds an order with ready-for-delivery items
 * unless one is supplied.
 *
 * @param  array<string, mixed>  $attrs
 */
function makeDelivery(Branch $branch, ?Order $order = null, array $attrs = []): Delivery
{
    $order ??= deliverableOrder($branch);

    return Delivery::factory()->for($branch)->create(array_merge([
        'order_id' => $order->id,
    ], $attrs));
}

/**
 * Persist an invoice in a branch, building an order with items unless one is
 * supplied. Defaults to a 1,00,000 paise total (overridable via $attrs).
 *
 * @param  array<string, mixed>  $attrs
 */
function makeInvoice(Branch $branch, ?Order $order = null, array $attrs = []): Invoice
{
    $order ??= deliverableOrder($branch);

    return Invoice::factory()->for($branch)->create(array_merge([
        'order_id' => $order->id,
        'customer_id' => $order->customer_id,
    ], $attrs));
}

/**
 * Dispatch a delivery (issues an OTP) as the given user.
 */
function dispatchDelivery(TestCase $test, User $user, int $deliveryId): TestResponse
{
    return $test->withHeaders(bearer($user))
        ->postJson("/api/v1/deliveries/{$deliveryId}/dispatch");
}

/**
 * Confirm a delivery with an OTP. The endpoint is idempotent; a fresh key is
 * generated unless one is supplied (pass a fixed key to test replay).
 */
function confirmDelivery(TestCase $test, User $user, int $deliveryId, string $otp, ?string $key = null): TestResponse
{
    $key ??= (string) Str::uuid();

    return $test->withHeaders(bearer($user) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/deliveries/{$deliveryId}/confirm", ['otp' => $otp]);
}

/**
 * Create a cut bundle in a branch whose order_item sits in a given state.
 */
function cutBundleFor(Branch $branch, string $itemState = OrderItem::STATE_TAILORING, int $pieces = 4): CutBundle
{
    $item = productionItem($branch, $itemState);
    $roll = fabricRoll($branch, 20.0);

    return CutBundle::factory()->for($branch)->create([
        'order_item_id' => $item->id,
        'fabric_roll_id' => $roll->id,
        'pieces_count' => $pieces,
    ]);
}
