<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Services;

use App\Models\User;
use App\Modules\Delivery\Exceptions\DeliveryException;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Models\DeliveryAttempt;
use App\Modules\Finance\Services\BalanceService;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Services\StateTransitionService;
use App\Modules\Shared\Services\NotificationDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Orchestrates the delivery lifecycle: create → dispatch (issues an OTP) →
 * confirm (verifies the OTP and atomically transitions the order's ready items
 * to Delivered, which frees their rack slots via the Phase 13 listener) — with
 * failed-attempt logging and cancellation as side paths.
 */
final class DeliveryService
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly StateTransitionService $transitions,
        private readonly NotificationDispatcher $notifications,
        private readonly BalanceService $balances,
    ) {}

    /**
     * Customer fulfillment balance gate. The full order balance must be clear
     * before any item leaves the shop — this mirrors the Front Desk pickup
     * HandoverService check and reuses the same BalanceService source of truth
     * (integer paise, no float math). Blocks home-delivery / courier dispatch and
     * confirmation identically to counter pickup.
     */
    private function assertBalanceClear(Delivery $delivery): void
    {
        $outstandingPaise = $this->balances->outstandingForOrder($delivery->order_id)['outstanding_paise'];

        if ($outstandingPaise > 0) {
            throw DeliveryException::balancePending($outstandingPaise);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Delivery
    {
        /** @var Order $order */
        $order = Order::query()->findOrFail($data['order_id']);

        $mode = is_string($data['mode'] ?? null) ? $data['mode'] : Delivery::MODE_PICKUP;

        $charges = array_key_exists('delivery_charges_paise', $data) && $data['delivery_charges_paise'] !== null
            ? (int) $data['delivery_charges_paise']
            : $order->delivery_charges_paise;

        return Delivery::query()->create([
            'order_id' => $order->id,
            'branch_id' => $order->branch_id,
            'mode' => $mode,
            'address_snapshot' => $this->resolveAddressSnapshot($data, $order),
            'courier_partner' => $data['courier_partner'] ?? null,
            'tracking_no' => $data['tracking_no'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => Delivery::STATUS_SCHEDULED,
            'delivery_charges_paise' => $charges,
            'created_by' => $actor->id,
        ]);
    }

    /**
     * Issue a fresh OTP and mark the delivery dispatched. May be called again to
     * re-issue after expiry or a lock-out. The raw code leaves the system only
     * through the notification channel.
     */
    public function dispatch(Delivery $delivery): Delivery
    {
        if ($delivery->isTerminal()) {
            throw $delivery->status === Delivery::STATUS_CANCELLED
                ? DeliveryException::cancelled()
                : DeliveryException::alreadyCompleted();
        }

        // Balance gate: never dispatch (issue OTP / mark dispatched) while the
        // order still owes money. Blocks before any side effect.
        $this->assertBalanceClear($delivery);

        return DB::transaction(function () use ($delivery): Delivery {
            $raw = $this->otp->issue($delivery);

            $delivery->update([
                'status' => Delivery::STATUS_DISPATCHED,
                'dispatched_at' => now(),
            ]);

            $this->notifications->send('sms', $this->recipient($delivery), [
                'template' => 'delivery_otp',
                'otp' => $raw,
                'delivery_id' => $delivery->id,
            ]);

            return $delivery->refresh();
        });
    }

    /**
     * Verify the OTP, then transition every ready-for-delivery item on the order
     * to Delivered (atomic with marking the delivery complete). The Delivered
     * edge fires the Phase 13 listener that frees each item's rack slot.
     */
    public function confirm(Delivery $delivery, string $otp, User $actor): Delivery
    {
        if ($delivery->status === Delivery::STATUS_CANCELLED) {
            throw DeliveryException::cancelled();
        }

        if ($delivery->status === Delivery::STATUS_DELIVERED) {
            throw DeliveryException::alreadyCompleted();
        }

        if (!$delivery->isDispatched()) {
            throw DeliveryException::notDispatched();
        }

        // Balance gate (re-checked server-side, never trust the UI): block
        // confirmation while any balance remains — no Delivered transition, no
        // rack-slot release, no completion record.
        $this->assertBalanceClear($delivery);

        // Verify outside the delivery transaction: a wrong attempt must persist
        // its incremented counter even though confirmation aborts. Only a
        // correct code (which marks the OTP used) proceeds to the transitions.
        $this->otp->verify($delivery, $otp);

        return DB::transaction(function () use ($delivery, $actor): Delivery {
            $items = OrderItem::query()
                ->where('order_id', $delivery->order_id)
                ->where('state', OrderItem::STATE_READY_FOR_DELIVERY)
                ->get();

            foreach ($items as $item) {
                $this->transitions->transition(
                    $item->id,
                    OrderItem::STATE_DELIVERED,
                    $actor,
                    (string) Str::uuid(),
                    'Delivery confirmed (delivery #' . $delivery->id . ')',
                );
            }

            $delivery->update([
                'status' => Delivery::STATUS_DELIVERED,
                'completed_at' => now(),
            ]);

            return $delivery->refresh();
        });
    }

    public function attempt(Delivery $delivery, string $reasonCode, ?string $notes, User $actor): DeliveryAttempt
    {
        if ($delivery->isTerminal()) {
            throw $delivery->status === Delivery::STATUS_CANCELLED
                ? DeliveryException::cancelled()
                : DeliveryException::alreadyCompleted();
        }

        return DB::transaction(function () use ($delivery, $reasonCode, $notes, $actor): DeliveryAttempt {
            $attempt = DeliveryAttempt::query()->create([
                'delivery_id' => $delivery->id,
                'branch_id' => $delivery->branch_id,
                'attempted_at' => now(),
                'attempted_by' => $actor->id,
                'reason_code' => $reasonCode,
                'reason_notes' => $notes,
            ]);

            $delivery->update(['status' => Delivery::STATUS_ATTEMPTED]);

            return $attempt;
        });
    }

    public function cancel(Delivery $delivery, ?string $reason, User $actor): Delivery
    {
        if ($delivery->status === Delivery::STATUS_DELIVERED) {
            throw DeliveryException::alreadyCompleted();
        }

        if ($delivery->status === Delivery::STATUS_CANCELLED) {
            throw DeliveryException::cancelled();
        }

        $delivery->update(['status' => Delivery::STATUS_CANCELLED]);

        activity('delivery')
            ->performedOn($delivery)
            ->causedBy($actor)
            ->event('delivery-cancelled')
            ->withProperties(['reason' => $reason])
            ->log('Delivery cancelled');

        return $delivery->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveAddressSnapshot(array $data, Order $order): ?string
    {
        if (array_key_exists('address_snapshot', $data) && $data['address_snapshot'] !== null) {
            return is_array($data['address_snapshot'])
                ? (string) json_encode($data['address_snapshot'])
                : (string) $data['address_snapshot'];
        }

        $order->loadMissing('customer');
        $address = $order->customer?->address;

        return $address !== null ? (string) json_encode(['address' => $address]) : null;
    }

    private function recipient(Delivery $delivery): string
    {
        $delivery->loadMissing('order.customer');

        return $delivery->order->customer->phone ?? 'unknown';
    }
}
