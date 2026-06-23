<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Finance\Services\BalanceService;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\WhatsappNotification;

/**
 * Builds WhatsApp message bodies from real order / payment / rack data. The
 * counter can edit the generated text before sending.
 */
final class WhatsappMessageBuilder
{
    public function __construct(private readonly BalanceService $balances) {}

    public function build(Order $order, string $eventType): string
    {
        $order->loadMissing('customer', 'items');

        $name = $order->customer?->name ?? 'Customer';
        $code = $order->order_code;
        $delivery = $order->expected_delivery_date?->format('d M Y') ?? '—';

        $bal = $this->balances->outstandingForOrder($order->id);
        $total = $this->inr($bal['invoiced_paise']);
        $paid = $this->inr($bal['paid_paise']);
        $balance = $this->inr($bal['outstanding_paise']);
        $hasBalance = $bal['outstanding_paise'] > 0;

        return match ($eventType) {
            WhatsappNotification::EVENT_ORDER_CONFIRMED =>
                "Hi {$name}, your order {$code} is confirmed. Delivery: {$delivery}. "
                . "Total {$total}, Advance {$paid}, Balance {$balance}. — Solo Shirts India",

            WhatsappNotification::EVENT_ORDER_READY =>
                "Hi {$name}, order {$code} is ready for pickup{$this->rackHint($order)}. "
                . ($hasBalance ? "Balance {$balance} pending — please pay on pickup. " : '')
                . '— Solo Shirts India',

            WhatsappNotification::EVENT_BALANCE_REMINDER =>
                "Hi {$name}, a balance of {$balance} is pending on order {$code}. "
                . 'You can pay by cash, UPI or bank transfer at the counter. — Solo Shirts India',

            WhatsappNotification::EVENT_ORDER_DELIVERED =>
                "Hi {$name}, order {$code} has been handed over. Thank you for choosing Solo Shirts India!",

            WhatsappNotification::EVENT_DELIVERY_RESCHEDULED =>
                "Hi {$name}, the delivery date for order {$code} is now {$delivery}. — Solo Shirts India",

            default => "Update on your order {$code} from Solo Shirts India.",
        };
    }

    private function rackHint(Order $order): string
    {
        $slot = RackSlot::query()
            ->whereIn('current_order_item_id', $order->items->pluck('id'))
            ->value('slot_code');

        return $slot !== null ? " (Ready Rack {$slot})" : '';
    }

    private function inr(int $paise): string
    {
        return '₹' . number_format($paise / 100, 0);
    }
}
