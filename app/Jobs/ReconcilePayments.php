<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentReconciliationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ReconcilePayments implements ShouldQueue
{
    use Queueable;

    public function handle(PaymentReconciliationService $reconciliation): void
    {
        Payment::query()
            ->where('status', Payment::STATUS_PENDING)
            ->whereHas('order', fn ($query) => $query->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_EXPIRED]))
            ->with('order')
            ->chunkById(100, function ($payments) use ($reconciliation): void {
                foreach ($payments as $payment) {
                    try {
                        $reconciliation->reconcile($payment);
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                }
            });
    }
}
