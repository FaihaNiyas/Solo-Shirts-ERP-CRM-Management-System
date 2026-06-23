<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Order\Exceptions\OrderException;
use App\Modules\Order\Models\AlterationRequest;
use App\Modules\Order\Models\AlterationStatusLog;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Support\Facades\DB;

/**
 * Creates customer post-delivery alteration requests. The original sub-order must
 * be delivered, and (via the branch-scoped lookup) belong to the actor's branch.
 * Never touches production state or the original invoice.
 */
final class AlterationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): AlterationRequest
    {
        $itemId = (int) ($data['original_order_item_id'] ?? 0);

        /** @var OrderItem|null $item */
        $item = OrderItem::query()->with('order')->find($itemId); // branch-scoped

        if ($item === null) {
            throw OrderException::alterationItemNotFound();
        }
        if ((string) $item->state !== OrderItem::STATE_DELIVERED) {
            throw OrderException::itemNotDeliveredForAlteration();
        }

        $charge = isset($data['estimated_charge']) && $data['estimated_charge'] !== null && $data['estimated_charge'] !== ''
            ? (int) round(((float) $data['estimated_charge']) * 100)
            : null;

        return AlterationRequest::query()->create([
            'original_order_id' => $item->order_id,
            'original_order_item_id' => $item->id,
            'customer_id' => $item->order?->customer_id,
            'requested_by_user_id' => $actor->id,
            'issue_type' => $data['issue_type'],
            'issue_description' => $data['issue_description'],
            'priority' => $data['priority'] ?? AlterationRequest::PRIORITY_NORMAL,
            'charge_required' => (bool) ($data['charge_required'] ?? false),
            'estimated_charge_paise' => $charge,
            'status' => AlterationRequest::STATUS_INTAKE,
            'photo_path' => $data['photo_path'] ?? null,
        ]);
    }

    /**
     * Move an alteration through its workflow and append an audit log entry.
     * This NEVER touches the original order_item.state or the original invoice —
     * it is a self-contained customer-alteration status, not internal QC rework.
     */
    public function updateStatus(AlterationRequest $alteration, string $to, ?string $notes, User $actor): AlterationRequest
    {
        $from = $alteration->status;

        if (!$alteration->canTransitionTo($to)) {
            throw OrderException::invalidAlterationTransition($from, $to);
        }

        return DB::transaction(function () use ($alteration, $from, $to, $notes, $actor): AlterationRequest {
            $alteration->status = $to;

            if ($to === AlterationRequest::STATUS_DELIVERED) {
                $alteration->completed_at = now();
            }
            if ($to === AlterationRequest::STATUS_CANCELLED) {
                $alteration->cancelled_at = now();
            }

            $alteration->save();

            AlterationStatusLog::query()->create([
                'alteration_request_id' => $alteration->id,
                'previous_status' => $from,
                'new_status' => $to,
                'changed_by' => $actor->id,
                'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
            ]);

            return $alteration;
        });
    }
}
