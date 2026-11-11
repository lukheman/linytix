<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Checkout\PaymentConfirmationService;
use App\Services\Payment\PaymentManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReconcilePayments implements ShouldQueue
{
    use Queueable;

    public function handle(PaymentManager $manager, PaymentConfirmationService $confirmation): void
    {
        Payment::query()
            ->where('status', Payment::STATUS_PENDING)
            ->whereHas('order', fn ($query) => $query->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_EXPIRED]))
            ->with('order')
            ->chunkById(100, function ($payments) use ($manager, $confirmation): void {
                foreach ($payments as $payment) {
                    $order = $payment->order;

                    if (! $order) {
                        continue;
                    }

                    try {
                        $status = $manager->gateway($payment->gateway)->getStatus($payment);

                        if ($status === Payment::STATUS_SUCCESS) {
                            $confirmation->confirm($order, $payment);

                            continue;
                        }

                        if ($status !== Payment::STATUS_PENDING && $status !== $payment->status) {
                            $payment->forceFill([
                                'status' => $status,
                                'paid_at' => null,
                            ])->save();
                        }
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                }
            });
    }
}
