<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Order\Models\AlterationRequest;
use App\Modules\Order\Models\AlterationStatusLog;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->fd = makeUser($this->branch, 'Front Desk');
});

/**
 * A minimal valid post-delivery alteration intake payload for an order_item.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function alterationPayload(int $itemId, array $overrides = []): array
{
    return array_merge([
        'original_order_item_id' => $itemId,
        'issue_type' => 'fitting_issue',
        'issue_description' => 'Sleeves run long; needs shortening after delivery.',
        'priority' => 'normal',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $payload
 */
function postAlteration(User $user, array $payload): TestResponse
{
    return test()->withHeaders(bearer($user) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/alterations', $payload);
}

it('creates an alteration for a delivered sub-order', function () {
    $item = productionItem($this->branch, OrderItem::STATE_DELIVERED);

    $res = postAlteration($this->fd, alterationPayload($item->id, [
        'charge_required' => true,
        'estimated_charge' => 250.50,
    ]))->assertCreated()->assertJsonPath('data.status', 'intake');

    $row = AlterationRequest::query()->find($res->json('data.alteration_id'));

    expect($row)->not->toBeNull()
        ->and($row->status)->toBe('intake')
        ->and($row->branch_id)->toBe($this->branch->id)
        ->and($row->original_order_id)->toBe($item->order_id)
        ->and($row->original_order_item_id)->toBe($item->id)
        ->and($row->customer_id)->toBe($item->order->customer_id)
        ->and($row->requested_by_user_id)->toBe($this->fd->id)
        ->and($row->charge_required)->toBeTrue()
        ->and($row->estimated_charge_paise)->toBe(25050); // rupees -> paise
});

it('rejects an alteration for a sub-order that is not yet delivered (422 ITEM_NOT_DELIVERED)', function () {
    $item = productionItem($this->branch, OrderItem::STATE_READY_FOR_DELIVERY);

    postAlteration($this->fd, alterationPayload($item->id))
        ->assertStatus(422)
        ->assertJsonPath('code', 'ITEM_NOT_DELIVERED');

    expect(AlterationRequest::query()->count())->toBe(0);
});

it('enforces branch scoping on the source sub-order (404 ALTERATION_ITEM_NOT_FOUND)', function () {
    $other = makeBranch(['code' => 'OTHER']);
    $item = productionItem($other, OrderItem::STATE_DELIVERED);

    postAlteration($this->fd, alterationPayload($item->id))
        ->assertStatus(404)
        ->assertJsonPath('code', 'ALTERATION_ITEM_NOT_FOUND');

    expect(AlterationRequest::query()->count())->toBe(0);
});

it('requires an issue type and description', function () {
    $item = productionItem($this->branch, OrderItem::STATE_DELIVERED);

    postAlteration($this->fd, [
        'original_order_item_id' => $item->id,
    ])->assertStatus(422)->assertJsonValidationErrors(['issue_type', 'issue_description']);
});

it('rejects an unknown issue type (no QC defect codes)', function () {
    $item = productionItem($this->branch, OrderItem::STATE_DELIVERED);

    postAlteration($this->fd, alterationPayload($item->id, ['issue_type' => 'qc_fail']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['issue_type']);
});

it('rejects a negative estimated charge', function () {
    $item = productionItem($this->branch, OrderItem::STATE_DELIVERED);

    postAlteration($this->fd, alterationPayload($item->id, ['estimated_charge' => -5]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['estimated_charge']);
});

it('does not change production state or reopen the order when an alteration is filed', function () {
    $item = productionItem($this->branch, OrderItem::STATE_DELIVERED);

    postAlteration($this->fd, alterationPayload($item->id))->assertCreated();

    expect((string) OrderItem::query()->find($item->id)->state)->toBe(OrderItem::STATE_DELIVERED);
});

it('does not grant Front Desk any production transition via the delivered item (403)', function () {
    $item = productionItem($this->branch, OrderItem::STATE_DELIVERED);

    // Even the QC rework state is out of reach — alteration intake grants no
    // production rights. (delivered target has no required notes, so the request
    // clears validation and is rejected by the policy, proving the permission gap.)
    transitionItem($this, $this->fd, $item->id, ['to' => 'delivered'])->assertStatus(403);
});

it('forbids alteration intake without the alterations.create permission (403)', function () {
    $staff = makeUser($this->branch, 'Measurement Staff'); // no alterations.* permissions
    $item = productionItem($this->branch, OrderItem::STATE_DELIVERED);

    postAlteration($staff, alterationPayload($item->id))->assertStatus(403);
});

it('forbids listing alterations without the alterations.view permission (403)', function () {
    $staff = makeUser($this->branch, 'Measurement Staff');

    $this->withHeaders(bearer($staff))->getJson('/api/v1/alterations')->assertStatus(403);
});

it('lists alterations and filters by status and order', function () {
    $a = productionItem($this->branch, OrderItem::STATE_DELIVERED);
    $b = productionItem($this->branch, OrderItem::STATE_DELIVERED);

    postAlteration($this->fd, alterationPayload($a->id))->assertCreated();
    postAlteration($this->fd, alterationPayload($b->id, ['priority' => 'urgent']))->assertCreated();

    $this->withHeaders(bearer($this->fd))->getJson('/api/v1/alterations')
        ->assertOk()->assertJsonCount(2, 'data');

    $this->withHeaders(bearer($this->fd))->getJson('/api/v1/alterations?status=intake')
        ->assertOk()->assertJsonCount(2, 'data');

    $this->withHeaders(bearer($this->fd))->getJson('/api/v1/alterations?status=ready')
        ->assertOk()->assertJsonCount(0, 'data');

    $this->withHeaders(bearer($this->fd))->getJson("/api/v1/alterations?order_id={$a->order_id}")
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.order_code', $a->order->order_code);
});

it('finds an alteration by the customer phone last four', function () {
    $item = productionItem($this->branch, OrderItem::STATE_DELIVERED);
    $last4 = $item->order->customer->phone_last4;

    postAlteration($this->fd, alterationPayload($item->id))->assertCreated();

    $this->withHeaders(bearer($this->fd))->getJson("/api/v1/alterations?q={$last4}")
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.phone_masked', '****' . $last4);
});

it('returns alteration detail with the full issue description', function () {
    $item = productionItem($this->branch, OrderItem::STATE_DELIVERED);
    $res = postAlteration($this->fd, alterationPayload($item->id))->assertCreated();
    $id = $res->json('data.alteration_id');

    $this->withHeaders(bearer($this->fd))->getJson("/api/v1/alterations/{$id}")
        ->assertOk()
        ->assertJsonPath('data.issue_type', 'fitting_issue')
        ->assertJsonPath('data.issue_description', 'Sleeves run long; needs shortening after delivery.')
        ->assertJsonPath('data.created_by', $this->fd->name);
});

it('stores an uploaded intake photo and exposes a signed url', function () {
    Storage::fake();
    $item = productionItem($this->branch, OrderItem::STATE_DELIVERED);

    $res = $this->withHeaders(bearer($this->fd) + ['Idempotency-Key' => (string) Str::uuid(), 'Accept' => 'application/json'])
        ->post('/api/v1/alterations', alterationPayload($item->id) + [
            'photo' => UploadedFile::fake()->create('issue.jpg', 120, 'image/jpeg'),
        ])->assertCreated();

    $row = AlterationRequest::query()->find($res->json('data.alteration_id'));
    expect($row->photo_path)->not->toBeNull();
    Storage::assertExists($row->photo_path);

    $this->withHeaders(bearer($this->fd))->getJson("/api/v1/alterations/{$row->id}")
        ->assertOk()
        ->assertJsonPath('data.photo_url', fn ($url) => is_string($url) && str_contains($url, 'signature='));
});

it('rejects a non-image intake photo (422)', function () {
    Storage::fake();
    $item = productionItem($this->branch, OrderItem::STATE_DELIVERED);

    $this->withHeaders(bearer($this->fd) + ['Idempotency-Key' => (string) Str::uuid(), 'Accept' => 'application/json'])
        ->post('/api/v1/alterations', alterationPayload($item->id) + [
            'photo' => UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf'),
        ])->assertStatus(422)->assertJsonValidationErrors(['photo']);
});

// --- Phone visibility hotfix --------------------------------------------------

it('shows the full customer phone to Front Desk in the alteration list', function () {
    $item = productionItem($this->branch, OrderItem::STATE_DELIVERED);
    $customer = $item->order->customer;
    postAlteration($this->fd, alterationPayload($item->id))->assertCreated();

    $this->withHeaders(bearer($this->fd))->getJson('/api/v1/alterations')
        ->assertOk()
        ->assertJsonPath('data.0.phone', $customer->phone)
        ->assertJsonPath('data.0.phone_masked', '****' . $customer->phone_last4);
});

it('shows the full customer phone to Front Desk in the alteration detail', function () {
    $item = productionItem($this->branch, OrderItem::STATE_DELIVERED);
    $customer = $item->order->customer;
    $res = postAlteration($this->fd, alterationPayload($item->id))->assertCreated();
    $id = $res->json('data.alteration_id');

    $this->withHeaders(bearer($this->fd))->getJson("/api/v1/alterations/{$id}")
        ->assertOk()
        ->assertJsonPath('data.phone', $customer->phone)
        ->assertJsonPath('data.phone_masked', '****' . $customer->phone_last4);
});

it('shows the full customer phone to Admin in list and detail', function () {
    $admin = makeUser($this->branch, 'Admin');
    $item = productionItem($this->branch, OrderItem::STATE_DELIVERED);
    $phone = $item->order->customer->phone;
    $res = postAlteration($this->fd, alterationPayload($item->id))->assertCreated();
    $id = $res->json('data.alteration_id');

    $this->withHeaders(bearer($admin))->getJson('/api/v1/alterations')
        ->assertOk()->assertJsonPath('data.0.phone', $phone);
    $this->withHeaders(bearer($admin))->getJson("/api/v1/alterations/{$id}")
        ->assertOk()->assertJsonPath('data.phone', $phone);
});

it('hides the full phone from an alterations viewer who is neither Front Desk nor Admin', function () {
    $item = productionItem($this->branch, OrderItem::STATE_DELIVERED);
    $customer = $item->order->customer;
    postAlteration($this->fd, alterationPayload($item->id))->assertCreated();

    // A bare viewer: holds alterations.view but no full-phone role.
    $viewer = makeUser($this->branch);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->branch->id);
    $viewer->givePermissionTo('alterations.view');

    $this->withHeaders(bearer($viewer))->getJson('/api/v1/alterations')
        ->assertOk()
        ->assertJsonPath('data.0.phone', null)
        ->assertJsonPath('data.0.phone_masked', '****' . $customer->phone_last4);
});

it('never exposes the customer phone through the signed photo route', function () {
    Storage::fake();
    $item = productionItem($this->branch, OrderItem::STATE_DELIVERED);
    $phone = $item->order->customer->phone;

    $res = $this->withHeaders(bearer($this->fd) + ['Idempotency-Key' => (string) Str::uuid(), 'Accept' => 'application/json'])
        ->post('/api/v1/alterations', alterationPayload($item->id) + [
            'photo' => UploadedFile::fake()->create('issue.jpg', 80, 'image/jpeg'),
        ])->assertCreated();
    $id = $res->json('data.alteration_id');

    $photoUrl = $this->withHeaders(bearer($this->fd))->getJson("/api/v1/alterations/{$id}")
        ->assertOk()->json('data.photo_url');

    $photoRes = $this->get($photoUrl);
    $photoRes->assertOk();
    expect($photoRes->streamedContent())->not->toContain($phone);
});

// --- Phase 5B: status workflow ------------------------------------------------

/**
 * Create an alteration in the given status against a delivered sub-order.
 */
function makeAlteration($branch, string $status = AlterationRequest::STATUS_INTAKE): AlterationRequest
{
    $item = productionItem($branch, OrderItem::STATE_DELIVERED);

    return AlterationRequest::query()->create([
        'branch_id' => $branch->id,
        'original_order_id' => $item->order_id,
        'original_order_item_id' => $item->id,
        'customer_id' => $item->order->customer_id,
        'requested_by_user_id' => null,
        'issue_type' => 'fitting_issue',
        'issue_description' => 'Needs a fit correction.',
        'priority' => 'normal',
        'charge_required' => false,
        'status' => $status,
    ]);
}

/**
 * @param  array<string, mixed>  $body
 */
function patchStatus(User $user, int $id, array $body): TestResponse
{
    return test()->withHeaders(bearer($user))->patchJson("/api/v1/alterations/{$id}/status", $body);
}

it('moves intake -> approved and logs the transition', function () {
    $admin = makeUser($this->branch, 'Admin');
    $alt = makeAlteration($this->branch, 'intake');

    patchStatus($admin, $alt->id, ['status' => 'approved', 'notes' => 'Customer approved'])
        ->assertOk()
        ->assertJsonPath('data.status', 'approved')
        ->assertJsonPath('data.previous_status', 'intake')
        ->assertJsonPath('data.updated_by', $admin->name);

    $log = AlterationStatusLog::query()->where('alteration_request_id', $alt->id)->first();
    expect($alt->refresh()->status)->toBe('approved')
        ->and($log)->not->toBeNull()
        ->and($log->previous_status)->toBe('intake')
        ->and($log->new_status)->toBe('approved')
        ->and($log->changed_by)->toBe($admin->id)
        ->and($log->notes)->toBe('Customer approved');
});

it('moves approved -> in_alteration', function () {
    $admin = makeUser($this->branch, 'Admin');
    $alt = makeAlteration($this->branch, 'approved');

    patchStatus($admin, $alt->id, ['status' => 'in_alteration'])
        ->assertOk()->assertJsonPath('data.status', 'in_alteration');
});

it('moves in_alteration -> ready', function () {
    $admin = makeUser($this->branch, 'Admin');
    $alt = makeAlteration($this->branch, 'in_alteration');

    patchStatus($admin, $alt->id, ['status' => 'ready'])
        ->assertOk()->assertJsonPath('data.status', 'ready');
});

it('moves ready -> delivered and sets completed_at', function () {
    $admin = makeUser($this->branch, 'Admin');
    $alt = makeAlteration($this->branch, 'ready');

    patchStatus($admin, $alt->id, ['status' => 'delivered'])
        ->assertOk()->assertJsonPath('data.status', 'delivered');

    expect($alt->refresh()->completed_at)->not->toBeNull()
        ->and($alt->cancelled_at)->toBeNull();
});

it('sets cancelled_at when cancelled', function () {
    $admin = makeUser($this->branch, 'Admin');
    $alt = makeAlteration($this->branch, 'intake');

    patchStatus($admin, $alt->id, ['status' => 'cancelled', 'notes' => 'Customer changed mind'])
        ->assertOk()->assertJsonPath('data.status', 'cancelled');

    expect($alt->refresh()->cancelled_at)->not->toBeNull()
        ->and($alt->completed_at)->toBeNull();
});

it('blocks an illegal skip intake -> ready (422 INVALID_ALTERATION_TRANSITION)', function () {
    $admin = makeUser($this->branch, 'Admin');
    $alt = makeAlteration($this->branch, 'intake');

    patchStatus($admin, $alt->id, ['status' => 'ready'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_ALTERATION_TRANSITION');

    expect($alt->refresh()->status)->toBe('intake');
});

it('blocks any transition out of delivered (final)', function () {
    $admin = makeUser($this->branch, 'Admin');
    $alt = makeAlteration($this->branch, 'delivered');

    patchStatus($admin, $alt->id, ['status' => 'cancelled'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_ALTERATION_TRANSITION');
});

it('blocks any transition out of cancelled (final)', function () {
    $admin = makeUser($this->branch, 'Admin');
    $alt = makeAlteration($this->branch, 'cancelled');

    patchStatus($admin, $alt->id, ['status' => 'approved'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_ALTERATION_TRANSITION');
});

it('rejects an unknown status value (422 validation)', function () {
    $admin = makeUser($this->branch, 'Admin');
    $alt = makeAlteration($this->branch, 'intake');

    patchStatus($admin, $alt->id, ['status' => 'shipped'])
        ->assertStatus(422)->assertJsonValidationErrors(['status']);
});

it('does not change the original order item state during the workflow', function () {
    $admin = makeUser($this->branch, 'Admin');
    $alt = makeAlteration($this->branch, 'intake');
    $itemId = $alt->original_order_item_id;

    foreach (['approved', 'in_alteration', 'ready', 'delivered'] as $next) {
        patchStatus($admin, $alt->id, ['status' => $next])->assertOk();
    }

    expect((string) OrderItem::query()->find($itemId)->state)->toBe(OrderItem::STATE_DELIVERED);
});

it('does not create or touch any invoice during the workflow', function () {
    $admin = makeUser($this->branch, 'Admin');
    $alt = makeAlteration($this->branch, 'ready');

    $before = Invoice::query()->where('order_id', $alt->original_order_id)->count();
    patchStatus($admin, $alt->id, ['status' => 'delivered'])->assertOk();
    $after = Invoice::query()->where('order_id', $alt->original_order_id)->count();

    expect($before)->toBe(0)->and($after)->toBe(0);
});

it('enforces branch scoping on status updates (404)', function () {
    $admin = makeUser($this->branch, 'Admin');
    $other = makeBranch(['code' => 'OTHER']);
    $alt = makeAlteration($other, 'intake');

    patchStatus($admin, $alt->id, ['status' => 'approved'])->assertStatus(404);
    expect($alt->refresh()->status)->toBe('intake');
});

it('forbids a user without alterations.update from driving the workflow (403)', function () {
    // Front Desk holds alterations.deliver but NOT alterations.update.
    $alt = makeAlteration($this->branch, 'intake');

    patchStatus($this->fd, $alt->id, ['status' => 'approved'])->assertStatus(403);
    expect($alt->refresh()->status)->toBe('intake');

    $staff = makeUser($this->branch, 'Measurement Staff'); // no alteration perms at all
    patchStatus($staff, $alt->id, ['status' => 'approved'])->assertStatus(403);
});

it('lets Front Desk mark a ready alteration delivered (handover step)', function () {
    $alt = makeAlteration($this->branch, 'ready');

    patchStatus($this->fd, $alt->id, ['status' => 'delivered'])
        ->assertOk()->assertJsonPath('data.status', 'delivered');

    expect($alt->refresh()->completed_at)->not->toBeNull();
});

it('forbids Front Desk from cancelling (only deliver is permitted)', function () {
    $alt = makeAlteration($this->branch, 'ready');

    patchStatus($this->fd, $alt->id, ['status' => 'cancelled'])->assertStatus(403);
    expect($alt->refresh()->status)->toBe('ready');
});

it('exposes permission-filtered allowed_next_statuses + status history in detail', function () {
    $admin = makeUser($this->branch, 'Admin');
    $alt = makeAlteration($this->branch, 'intake');
    patchStatus($admin, $alt->id, ['status' => 'approved', 'notes' => 'ok'])->assertOk();

    // Admin (full workflow): approved -> [in_alteration, cancelled], history of 1.
    $this->withHeaders(bearer($admin))->getJson("/api/v1/alterations/{$alt->id}")
        ->assertOk()
        ->assertJsonPath('data.can_update_status', true)
        ->assertJsonPath('data.allowed_next_statuses', ['in_alteration', 'cancelled'])
        ->assertJsonPath('data.status_logs.0.new_status', 'approved')
        ->assertJsonPath('data.status_logs.0.changed_by', $admin->name);

    // Front Desk on an approved alteration: no delivered yet, no update right -> none.
    $this->withHeaders(bearer($this->fd))->getJson("/api/v1/alterations/{$alt->id}")
        ->assertOk()
        ->assertJsonPath('data.can_update_status', false)
        ->assertJsonPath('data.allowed_next_statuses', []);
});

it('offers Front Desk only [delivered] on a ready alteration', function () {
    $alt = makeAlteration($this->branch, 'ready');

    $this->withHeaders(bearer($this->fd))->getJson("/api/v1/alterations/{$alt->id}")
        ->assertOk()
        ->assertJsonPath('data.can_update_status', true)
        ->assertJsonPath('data.allowed_next_statuses', ['delivered']);
});
