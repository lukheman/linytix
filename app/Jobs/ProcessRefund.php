<?php

namespace App\Jobs;

use App\Models\Refund;
use App\Services\Payment\PaymentManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessRefund implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $refundId) {}

    public function handle(PaymentManager $manager): void
    {
        $refund = Refund::query()->with('payment')->find($this->refundId);

        if (! $refund || ! $refund->payment || $refund->status === Refund::STATUS_SUCCEEDED) {
            return;
        }

        $refund->forceFill(['status' => Refund::STATUS_PROCESSING])->save();

        try {
            $result = $manager->gateway($refund->payment->gateway)
                ->refund($refund->payment, $refund->amount, $refund->reason);

            $succeeded = ($result['status'] ?? null) === 'succeeded';

            $refund->forceFill([
                'status' => $succeeded ? Refund::STATUS_SUCCEEDED : Refund::STATUS_FAILED,
                'gateway_ref' => $result['gateway_ref'] ?? $refund->gateway_ref,
                'processed_at' => now(),
            ])->save();
        } catch (\Throwable $exception) {
            $refund->forceFill([
                'status' => Refund::STATUS_FAILED,
                'processed_at' => now(),
            ])->save();

            report($exception);
        }
    }
}
