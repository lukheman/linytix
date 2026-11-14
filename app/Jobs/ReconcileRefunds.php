<?php

namespace App\Jobs;

use App\Models\Refund;
use App\Services\Payment\PaymentManager;
use App\Services\Refund\RefundService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Menyelesaikan refund yang masih processing ketika callback gateway tidak
 * datang. Status diambil langsung dari gateway agar refund tidak menggantung.
 */
class ReconcileRefunds implements ShouldQueue
{
    use Queueable;

    public function handle(PaymentManager $manager, RefundService $service): void
    {
        Refund::query()
            ->where('status', Refund::STATUS_PROCESSING)
            ->whereNotNull('gateway_ref')
            ->with('payment')
            ->chunkById(100, function ($refunds) use ($manager, $service): void {
                foreach ($refunds as $refund) {
                    $payment = $refund->payment;

                    if (! $payment) {
                        continue;
                    }

                    try {
                        $status = $manager->gateway($payment->gateway)->getRefundStatus($refund);

                        if ($status === 'succeeded') {
                            $service->markSucceeded($refund, (string) $refund->gateway_ref);
                        } elseif ($status === 'failed') {
                            $service->markFailed($refund, 'Refund gagal di gateway.');
                        }
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                }
            });
    }
}
