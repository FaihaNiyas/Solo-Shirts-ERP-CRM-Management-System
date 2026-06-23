<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Order\Models\FrontDeskOrderDraft;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->fd = makeUser($this->branch, 'Front Desk');
});

function samplePayload(array $overrides = []): array
{
    return array_merge([
        'version' => 1,
        'activeStep' => 'subOrders',
        'subOrders' => [
            ['tempId' => 'a', 'productType' => 'trouser', 'measurementVersionId' => 7, 'basePrice' => 800, 'gstRate' => 5],
        ],
    ], $overrides);
}

it('lets Front Desk create a draft (no order created)', function () {
    $this->withHeaders(bearer($this->fd))
        ->postJson('/api/v1/front-desk/drafts', [
            'current_step' => 'customer', 'total_items' => 3, 'draft_payload' => samplePayload(),
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'active');

    expect(FrontDeskOrderDraft::query()->where('user_id', $this->fd->id)->count())->toBe(1)
        ->and(Order::query()->count())->toBe(0); // a draft is not an order — nothing in production
});

it('lets Front Desk autosave (update) its own draft', function () {
    $id = $this->withHeaders(bearer($this->fd))
        ->postJson('/api/v1/front-desk/drafts', ['draft_payload' => samplePayload()])->json('data.id');

    $this->withHeaders(bearer($this->fd))
        ->patchJson("/api/v1/front-desk/drafts/{$id}", [
            'current_step' => 'review', 'completed_count' => 2, 'total_items' => 10, 'draft_payload' => samplePayload(['activeStep' => 'review']),
        ])
        ->assertOk()
        ->assertJsonPath('data.completed_count', 2)
        ->assertJsonPath('data.current_step', 'review');

    $draft = FrontDeskOrderDraft::query()->find($id);
    expect($draft->last_saved_at)->not->toBeNull()->and($draft->total_items)->toBe(10);
});

it('lists only the open drafts of the requesting Front Desk user', function () {
    $mine = FrontDeskOrderDraft::query()->create(['branch_id' => $this->branch->id, 'user_id' => $this->fd->id, 'status' => 'active', 'draft_payload' => []]);
    FrontDeskOrderDraft::query()->create(['branch_id' => $this->branch->id, 'user_id' => $this->fd->id, 'status' => 'converted', 'draft_payload' => []]);
    FrontDeskOrderDraft::query()->create(['branch_id' => $this->branch->id, 'user_id' => $this->fd->id, 'status' => 'discarded', 'draft_payload' => []]);
    $otherFd = makeUser($this->branch, 'Front Desk');
    FrontDeskOrderDraft::query()->create(['branch_id' => $this->branch->id, 'user_id' => $otherFd->id, 'status' => 'active', 'draft_payload' => []]);

    $res = $this->withHeaders(bearer($this->fd))->getJson('/api/v1/front-desk/drafts')->assertOk();
    expect($res->json('data'))->toHaveCount(1)
        ->and($res->json('data.0.id'))->toBe($mine->id);
});

it('lets Admin see every open draft in the branch', function () {
    $admin = makeUser($this->branch, 'Admin');
    FrontDeskOrderDraft::query()->create(['branch_id' => $this->branch->id, 'user_id' => $this->fd->id, 'status' => 'active', 'draft_payload' => []]);
    $otherFd = makeUser($this->branch, 'Front Desk');
    FrontDeskOrderDraft::query()->create(['branch_id' => $this->branch->id, 'user_id' => $otherFd->id, 'status' => 'active', 'draft_payload' => []]);

    $this->withHeaders(bearer($admin))->getJson('/api/v1/front-desk/drafts')->assertOk()->assertJsonCount(2, 'data');
});

it('blocks access to another branch draft (404)', function () {
    $other = makeBranch(['code' => 'OTHER']);
    $otherUser = makeUser($other, 'Front Desk');
    $draft = FrontDeskOrderDraft::query()->create(['branch_id' => $other->id, 'user_id' => $otherUser->id, 'status' => 'active', 'draft_payload' => []]);

    $this->withHeaders(bearer($this->fd))->getJson("/api/v1/front-desk/drafts/{$draft->id}")->assertStatus(404);
});

it('forbids one Front Desk user editing another user draft (403)', function () {
    $otherFd = makeUser($this->branch, 'Front Desk');
    $draft = FrontDeskOrderDraft::query()->create(['branch_id' => $this->branch->id, 'user_id' => $otherFd->id, 'status' => 'active', 'draft_payload' => []]);

    $this->withHeaders(bearer($this->fd))->patchJson("/api/v1/front-desk/drafts/{$draft->id}", ['total_items' => 5])->assertStatus(403);
});

it('marks a draft converted and drops it from the open list', function () {
    $id = $this->withHeaders(bearer($this->fd))->postJson('/api/v1/front-desk/drafts', ['draft_payload' => samplePayload()])->json('data.id');

    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/front-desk/drafts/{$id}/convert")->assertOk()->assertJsonPath('data.status', 'converted');
    $this->withHeaders(bearer($this->fd))->getJson('/api/v1/front-desk/drafts')->assertOk()->assertJsonCount(0, 'data');
});

it('marks a draft discarded and drops it from the open list', function () {
    $id = $this->withHeaders(bearer($this->fd))->postJson('/api/v1/front-desk/drafts', ['draft_payload' => samplePayload()])->json('data.id');

    $this->withHeaders(bearer($this->fd))->deleteJson("/api/v1/front-desk/drafts/{$id}")->assertOk();
    expect(FrontDeskOrderDraft::query()->find($id)->status)->toBe('discarded');
    $this->withHeaders(bearer($this->fd))->getJson('/api/v1/front-desk/drafts')->assertOk()->assertJsonCount(0, 'data');
});

it('cancels the linked intake order when a draft is discarded', function () {
    $customer = Customer::factory()->for($this->branch)->create();
    $order = Order::factory()->for($this->branch)->for($customer)->create(['lifecycle_status' => 'intake_preparation']);
    OrderItem::factory()->for($this->branch)->for($order)->create(['state' => 'draft']);

    $id = $this->withHeaders(bearer($this->fd))
        ->postJson('/api/v1/front-desk/drafts', ['order_id' => $order->id, 'draft_payload' => samplePayload()])->json('data.id');

    $this->withHeaders(bearer($this->fd))->deleteJson("/api/v1/front-desk/drafts/{$id}")->assertOk();

    expect($order->refresh()->lifecycle_status)->toBe('cancelled');
});

it('preserves product type, measurement version and pricing in the payload', function () {
    $payload = samplePayload();
    $id = $this->withHeaders(bearer($this->fd))->postJson('/api/v1/front-desk/drafts', ['draft_payload' => $payload])->json('data.id');

    $this->withHeaders(bearer($this->fd))->getJson("/api/v1/front-desk/drafts/{$id}")
        ->assertOk()
        ->assertJsonPath('data.draft_payload.subOrders.0.productType', 'trouser')
        ->assertJsonPath('data.draft_payload.subOrders.0.measurementVersionId', 7)
        ->assertJsonPath('data.draft_payload.subOrders.0.basePrice', 800);
});

it('requires orders.create to use drafts (403)', function () {
    $staff = makeUser($this->branch, 'Measurement Staff'); // no orders.create

    $this->withHeaders(bearer($staff))->getJson('/api/v1/front-desk/drafts')->assertStatus(403);
    $this->withHeaders(bearer($staff))->postJson('/api/v1/front-desk/drafts', ['draft_payload' => []])->assertStatus(403);
});
