<?php

namespace App\Services\Checkout;

use App\Exceptions\LatePaymentStockUnavailable;
use App\Jobs\IssueTickets;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\TicketType;
use App\Notifications\OrderRefundRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PaymentConfirmationService
{
    public const RESULT_PAID = 'paid';

    public const RESULT_REFUNDED = 'refunded';

    public const RESULT_IGNORED = 'ignored';

    public const RESULT_AMOUNT_MISMATCH = 'amount_mismatch';

    /**
     * Konfirmasi pembayaran sukses dan terbitkan tiket bila memungkinkan.
     *
     * Order expired yang dibayar terlambat dicoba untuk mengambil stok lagi.
     * Jika stok tidak tersedia, refund otomatis dibuat.
     */
    public function confirm(Order $order, Payment $payment, ?int $amount = null): string
    {
        if ($amount !== null && $amount !== (int) $order->total_amount) {
            return self::RESULT_AMOUNT_MISMATCH;
        }

        try {
            $result = DB::transaction(function () use ($order, $payment): string {
                $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

                if (! $locked) {
                    return self::RESULT_IGNORED;
                }

                if ($locked->status === Order::STATUS_PAID) {
                    $this->markPaymentPaid($payment);

                    return self::RESULT_IGNORED;
                }

                if (! in_array($locked->status, [Order::STATUS_PENDING, Order::STATUS_EXPIRED], true)) {
                    return self::RESULT_IGNORED;
                }

                if ($locked->status === Order::STATUS_EXPIRED) {
                    $this->reserveStockForLatePayment($locked);
                }

                $this->markPaymentPaid($payment);

                $locked->forceFill([
                    'status' => Order::STATUS_PAID,
                    'paid_at' => now(),
                ])->save();

                return self::RESULT_PAID;
            });
        } catch (LatePaymentStockUnavailable) {
            $this->recordLatePaymentRefund($order, $payment);

            return self::RESULT_REFUNDED;
        }

        if ($result === self::RESULT_PAID) {
            IssueTickets::dispatch($order->getKey());
        }

        return $result;
    }

    private function markPaymentPaid(Payment $payment): void
    {
        if ($payment->status === Payment::STATUS_SUCCESS) {
            return;
        }

        $payment->forceFill([
            'status' => Payment::STATUS_SUCCESS,
            'paid_at' => now(),
        ])->save();
    }

    private function reserveStockForLatePayment(Order $order): void
    {
        $items = $order->items()->get();

        foreach ($items as $item) {
            $reserved = TicketType::query()
                ->whereKey($item->ticket_type_id)
                ->whereRaw('quota - sold_count >= ?', [(int) $item->quantity])
                ->increment('sold_count', (int) $item->quantity);

            if ($reserved !== 1) {
                // Transaksi akan rollback, termasuk stok item sebelumnya.
                throw new LatePaymentStockUnavailable;
            }
        }
    }

    private function recordLatePaymentRefund(Order $order, Payment $payment): void
    {
        $refund = DB::transaction(function () use ($order, $payment): Refund {
            $this->markPaymentPaid($payment);

            return Refund::query()->firstOrCreate(
                [
                    'order_id' => $order->getKey(),
                    'status' => Refund::STATUS_REQUESTED,
                ],
                [
                    'payment_id' => $payment->getKey(),
                    'amount' => (int) $order->total_amount,
                    'reason' => 'Pembayaran diterima setelah order kedaluwarsa dan stok tiket tidak tersedia.',
                ],
            );
        });

        if ($refund->wasRecentlyCreated) {
            Notification::route('mail', $order->buyer_email)
                ->notify(new OrderRefundRequested($order, $refund));
        }
    }
}
