<?php

namespace App\Services\Payment;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Checkout\PaymentConfirmationService;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentReconciliationService
{
    public const RESULT_PAID = 'paid';

    public const RESULT_REFUNDED = 'refunded';

    public const RESULT_UPDATED = 'updated';

    public const RESULT_UNCHANGED = 'unchanged';

    public const RESULT_IGNORED = 'ignored';

    public const RESULT_ERROR = 'error';

    public function __construct(
        private PaymentManager $manager,
        private PaymentConfirmationService $confirmation,
    ) {}

    /**
     * Cocokkan satu pembayaran dengan status gateway. Dipakai oleh job
     * rekonsiliasi dan aksi manual admin.
     *
     * @return array{result: string, previous_status: string, status: string, message: string}
     */
    public function reconcile(Payment $payment, ?User $admin = null, ?string $ip = null): array
    {
        if (! in_array($payment->status, [
            Payment::STATUS_PENDING,
            Payment::STATUS_FAILED,
            Payment::STATUS_EXPIRED,
        ], true)) {
            throw ValidationException::withMessages([
                'payment' => 'Pembayaran ini tidak dapat direkonsiliasi pada status saat ini.',
            ]);
        }

        $payment->loadMissing('order');
        $order = $payment->order;

        if (! $order) {
            throw ValidationException::withMessages([
                'payment' => 'Pembayaran tidak memiliki pesanan yang valid.',
            ]);
        }

        $previousStatus = (string) $payment->status;
        $gatewayStatus = $previousStatus;
        $result = self::RESULT_UNCHANGED;
        $error = null;

        try {
            $gatewayStatus = $this->manager->gateway($payment->gateway)->getStatus($payment);

            if ($gatewayStatus === Payment::STATUS_SUCCESS) {
                $confirmation = $this->confirmation->confirm($order, $payment);

                $result = match ($confirmation) {
                    PaymentConfirmationService::RESULT_PAID => self::RESULT_PAID,
                    PaymentConfirmationService::RESULT_REFUNDED => self::RESULT_REFUNDED,
                    default => self::RESULT_IGNORED,
                };
            } elseif ($gatewayStatus !== Payment::STATUS_PENDING && $gatewayStatus !== $payment->status) {
                $payment->forceFill([
                    'status' => $gatewayStatus,
                    'paid_at' => null,
                ])->save();

                $result = self::RESULT_UPDATED;
            }
        } catch (Throwable $exception) {
            report($exception);

            $result = self::RESULT_ERROR;
            $error = mb_substr($exception->getMessage(), 0, 500);
        }

        $payment->refresh();
        $finalStatus = (string) $payment->status;

        if ($admin) {
            $this->audit($admin, $payment, $order, [
                'previous_status' => $previousStatus,
                'gateway_status' => $gatewayStatus,
                'final_status' => $finalStatus,
                'result' => $result,
                'error' => $error,
            ], $ip);
        }

        return [
            'result' => $result,
            'previous_status' => $previousStatus,
            'status' => $finalStatus,
            'message' => $this->message($result, $finalStatus, $error),
        ];
    }

    private function message(string $result, string $status, ?string $error): string
    {
        return match ($result) {
            self::RESULT_PAID => 'Pembayaran berhasil dikonfirmasi dan pesanan ditandai lunas.',
            self::RESULT_REFUNDED => 'Pembayaran diterima, tetapi stok tiket tidak tersedia sehingga refund otomatis dibuat.',
            self::RESULT_UPDATED => 'Status pembayaran diperbarui menjadi '.$status.'.',
            self::RESULT_ERROR => 'Rekonsiliasi gagal: '.($error ?: 'terjadi kesalahan pada gateway.'),
            default => 'Status pembayaran tidak berubah ('.$status.').',
        };
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function audit(User $admin, Payment $payment, Order $order, array $meta, ?string $ip): void
    {
        AuditLog::query()->create([
            'user_id' => $admin->getKey(),
            'action' => 'payment.reconciled',
            'entity' => 'payment',
            'entity_id' => $payment->getKey(),
            'meta' => array_merge([
                'order_number' => $order->order_number,
                'payment_id' => $payment->getKey(),
            ], $meta),
            'ip_address' => $ip,
        ]);
    }
}
