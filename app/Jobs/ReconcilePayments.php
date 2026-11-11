<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\Payment\PaymentManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReconcilePayments implements ShouldQueue
{
    use Queueable;

    public function handle(PaymentManager $manager): void
    {
        Payment::query()
            ->where('status', Payment::STATUS_PENDING)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->chunkById(100, function ($payments) use ($manager): void {
                foreach ($payments as $payment) {
                    try {
                        $status = $manager->gateway($payment->gateway)->getStatus($payment);

                        if ($status !== $payment->status) {
                            $payment->forceFill([
                                'status' => $status,
                                'paid_at' => $status === Payment::STATUS_SUCCESS ? now() : $payment->paid_at,
                            ])->save();
                        }
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                }
            });
    }
}
