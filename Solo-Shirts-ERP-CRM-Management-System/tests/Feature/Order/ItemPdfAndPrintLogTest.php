<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\PrintLog;
use App\Modules\Printing\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Front Desk');
    $this->customer = Customer::factory()->for($this->branch)->create();
    $this->version = approvedVersionFor($this->branch, $this->customer);

    // A real order with two sub-orders, created through the public API.
    $payload = orderPayload($this->customer->id, $this->version->id, [
        'items' => [
            ['product_type' => 'shirt', 'quantity' => 1, 'measurement_version_id' => $this->version->id],
            ['product_type' => 'shirt', 'quantity' => 1, 'measurement_version_id' => $this->version->id],
        ],
    ]);

    $res = $this->withHeaders(bearer($this->user) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', $payload)
        ->assertCreated();

    $this->order = $res->json('data.id');
    $this->items = collect($res->json('data.items'))->pluck('id')->all();
});

it('generates one PDF per sub-order referencing the order item (no box required)', function () {
    $itemId = $this->items[0];

    $res = $this->withHeaders(bearer($this->user))
        ->getJson("/api/v1/orders/{$this->order}/items/{$itemId}/job-card")
        ->assertCreated()
        ->assertJsonPath('data.pdf_status', 'generated');

    expect($res->json('data.download_url'))->toContain('/documents/');

    expect(
        Document::query()
            ->where('kind', 'job_card')
            ->where('reference_type', OrderItem::class)
            ->where('reference_id', $itemId)
            ->exists()
    )->toBeTrue();
});

it('records a print and a reprint in the print log', function () {
    $itemId = $this->items[0];

    $this->withHeaders(bearer($this->user))
        ->postJson("/api/v1/orders/{$this->order}/items/{$itemId}/print-log", ['is_reprint' => false])
        ->assertCreated();

    $this->withHeaders(bearer($this->user))
        ->postJson("/api/v1/orders/{$this->order}/items/{$itemId}/print-log", ['is_reprint' => true, 'reason' => 'smudged'])
        ->assertCreated();

    expect(PrintLog::query()->where('order_item_id', $itemId)->count())->toBe(2)
        ->and(PrintLog::query()->where('order_item_id', $itemId)->where('is_reprint', true)->exists())->toBeTrue();
});
