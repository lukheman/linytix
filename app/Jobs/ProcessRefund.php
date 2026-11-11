<?php

namespace App\Jobs;

use App\Models\Refund;
use App\Services\Payment\PaymentManager;
use App\Services\Refund\RefundService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessRefund implements ShouldQueue
{
    use Queueable;

    /**
     * Refund tidak diulang otomatis oleh queue. Kegagalan ditandai
     * sebagai failed dan diproses ulang secara manual dari admin.
     */
    public int $tries = 1;

    public function __construct(
        public string $refundId,
        public ?int $processedBy = null,
    ) {}

    public function handle(PaymentManager $manager, RefundService $service): void
    {
        $claimed = DB::transaction(function (): ?Refund {
            $refund = Refund::query()
                ->with('payment')
                ->whereKey($this->refundId)
                ->lockForUpdate()
                ->first();

            if (! $refund || ! $refund->payment) {
                return null;
            }

            if (! in_array($refund->status, [Refund::STATUS_REQUESTED, Refund::STATUS_FAILED], true)) {
                return null;
            }

            $refund->forceFill([
                'status' => Refund::STATUS_PROCESSING,
                'error' => null,
                'processed_by' => $this->processedBy ?? $refund->processed_by,
            ])->save();

            return $refund;
        });

        if (! $claimed) {
            return;
        }

        try {
            $result = $manager->gateway($claimed->payment->gateway)
                ->refund($claimed->payment, $claimed->amount, $claimed->reason);

            if (($result['status'] ?? null) !== 'succeeded') {
                $service->markFailed(
                    $claimed,
                    (string) ($result['message'] ?? 'Gateway tidak mengembalikan status sukses.'),
                );

                return;
            }

            $service->markSucceeded($claimed, $result['gateway_ref'] ?? null);
        } catch (Throwable $exception) {
            $service->markFailed($claimed, $exception->getMessage());

            report($exception);
        }
    }
}
