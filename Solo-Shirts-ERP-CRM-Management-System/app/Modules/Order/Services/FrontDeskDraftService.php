<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Order\Models\FrontDeskOrderDraft;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Collection;

/**
 * Server-side Front Desk order drafts. A draft holds wizard state only — it never
 * confirms an order, creates an invoice/payment, or pushes anything to production.
 * Discarding a draft that already minted an intake order cancels that order
 * (releasing its production boxes) through the existing order-cancel path.
 */
final class FrontDeskDraftService
{
    public function __construct(private readonly OrderService $orders) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): FrontDeskOrderDraft
    {
        return FrontDeskOrderDraft::query()->create([
            'user_id' => $actor->id,
            'customer_id' => $data['customer_id'] ?? null,
            'family_member_id' => $data['family_member_id'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'title' => $data['title'] ?? null,
            'status' => FrontDeskOrderDraft::STATUS_ACTIVE,
            'current_step' => $data['current_step'] ?? null,
            'completed_count' => (int) ($data['completed_count'] ?? 0),
            'total_items' => (int) ($data['total_items'] ?? 0),
            'draft_payload' => $data['draft_payload'] ?? [],
            'last_saved_at' => now(),
        ]);
    }

    /**
     * Autosave. Only active/paused drafts may be updated; status can move between
     * active and paused (e.g. "Save & Pause") but never to converted/discarded here.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(FrontDeskOrderDraft $draft, array $data): FrontDeskOrderDraft
    {
        foreach (['customer_id', 'family_member_id', 'order_id', 'title', 'current_step', 'completed_count', 'total_items', 'draft_payload'] as $field) {
            if (array_key_exists($field, $data)) {
                $draft->{$field} = $data[$field];
            }
        }

        if (isset($data['status']) && in_array($data['status'], FrontDeskOrderDraft::OPEN_STATUSES, true)) {
            $draft->status = $data['status'];
        }

        $draft->last_saved_at = now();
        $draft->save();

        return $draft;
    }

    /**
     * Resume list: a Front Desk user sees their own open drafts; Owner/Admin see
     * every open draft in the branch. Converted/discarded are excluded.
     *
     * @return Collection<int, FrontDeskOrderDraft>
     */
    public function openForUser(User $actor): Collection
    {
        $query = FrontDeskOrderDraft::query()
            ->whereIn('status', FrontDeskOrderDraft::OPEN_STATUSES)
            ->with(['customer:id,name', 'user:id,name'])
            ->orderByDesc('last_saved_at');

        if (!$actor->hasAnyRole(['Owner', 'Admin'])) {
            $query->where('user_id', $actor->id);
        }

        return $query->get();
    }

    public function convert(FrontDeskOrderDraft $draft): FrontDeskOrderDraft
    {
        if ($draft->status !== FrontDeskOrderDraft::STATUS_CONVERTED) {
            $draft->status = FrontDeskOrderDraft::STATUS_CONVERTED;
            $draft->converted_at = now();
            $draft->save();
        }

        return $draft;
    }

    /**
     * Discard a draft. If it minted an intake_preparation order, cancel that order
     * so its production boxes/rack are released safely.
     */
    public function discard(FrontDeskOrderDraft $draft): FrontDeskOrderDraft
    {
        if ($draft->status === FrontDeskOrderDraft::STATUS_DISCARDED) {
            return $draft;
        }

        if ($draft->order_id !== null) {
            /** @var Order|null $order */
            $order = Order::query()->find($draft->order_id); // branch-scoped
            if ($order !== null && $order->lifecycle_status === Order::LIFECYCLE_INTAKE) {
                $this->orders->cancelOrder($order, 'Front Desk draft discarded');
            }
        }

        $draft->status = FrontDeskOrderDraft::STATUS_DISCARDED;
        $draft->discarded_at = now();
        $draft->save();

        return $draft;
    }
}
