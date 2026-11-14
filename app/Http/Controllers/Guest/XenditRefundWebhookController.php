<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Models\WebhookEvent;
use App\Services\Payment\PaymentManager;
use App\Services\Refund\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Callback refund Xendit (Refund API v3) berbentuk { event, data } dan dikirim
 * ke URL terpisah dari callback payment session. Token diverifikasi dengan
 * aturan yang sama.
 */
class XenditRefundWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaymentManager $payments,
        RefundService $refunds,
    ): JsonResponse {
        $driver = $payments->gateway('xendit');
        $signatureValid = $driver->verifyWebhookSignature($request);

        $payload = $request->all();
        $eventName = (string) ($payload['event'] ?? '');
        $data = (array) ($payload['data'] ?? []);
        $externalId = (string) ($data['refund_id'] ?? $data['id'] ?? '');

        if ($externalId === '') {
            $externalId = 'refund-'.hash('sha256', $request->getContent());
        }

        $event = WebhookEvent::query()->firstOrCreate(
            [
                'gateway' => $driver->name(),
                'external_id' => $externalId,
                'event_type' => $eventName !== '' ? $eventName : 'refund.unknown',
            ],
            [
                'payload' => $payload,
                'signature_valid' => $signatureValid,
                'status' => WebhookEvent::STATUS_RECEIVED,
            ],
        );

        if (! $signatureValid) {
            $event->forceFill([
                'signature_valid' => false,
                'status' => WebhookEvent::STATUS_INVALID_SIGNATURE,
                'processed_at' => now(),
            ])->save();

            return response()->json(['status' => 'invalid signature'], 403);
        }

        if ($event->status === WebhookEvent::STATUS_PROCESSED) {
            return response()->json(['status' => 'ok']);
        }

        if (! in_array($eventName, ['refund.succeeded', 'refund.failed'], true)) {
            return $this->finish($event, WebhookEvent::STATUS_IGNORED, 'Event refund tidak dikenal.');
        }

        $refund = $this->findRefund($externalId, (string) ($data['payment_id'] ?? ''));

        if (! $refund) {
            return $this->finish($event, WebhookEvent::STATUS_FAILED, 'Refund tidak ditemukan.');
        }

        $refund->loadMissing(['order', 'payment']);
        $referenceId = (string) ($data['reference_id'] ?? '');
        $expectedReference = 'refund-'.($refund->order?->order_number ?: $refund->payment?->getKey());

        if ($referenceId === '' || ! hash_equals($expectedReference, $referenceId)) {
            return $this->finish($event, WebhookEvent::STATUS_FAILED, 'Referensi order refund tidak sesuai.');
        }

        if ((string) ($data['payment_id'] ?? '') !== (string) $refund->payment?->gateway_payment_ref) {
            return $this->finish($event, WebhookEvent::STATUS_FAILED, 'Referensi pembayaran refund tidak sesuai.');
        }

        $currency = mb_strtoupper((string) ($data['currency'] ?? ''));

        if ($currency !== 'IDR') {
            return $this->finish($event, WebhookEvent::STATUS_FAILED, 'Mata uang refund tidak didukung.');
        }

        if (! isset($data['amount']) || (int) $data['amount'] !== (int) $refund->amount) {
            return $this->finish($event, WebhookEvent::STATUS_FAILED, 'Nominal refund tidak sesuai.');
        }

        if ($eventName === 'refund.succeeded') {
            $refunds->markSucceeded($refund, $externalId);
        } else {
            $refunds->markFailed(
                $refund,
                (string) ($data['failure_code'] ?? 'Refund gagal di gateway.'),
            );
        }

        return $this->finish($event, WebhookEvent::STATUS_PROCESSED, 'Status refund diperbarui.');
    }

    private function findRefund(string $refundId, string $paymentRef): ?Refund
    {
        $refund = Refund::query()->where('gateway_ref', $refundId)->first();

        if ($refund || $paymentRef === '') {
            return $refund;
        }

        return Refund::query()
            ->whereHas('payment', fn ($query) => $query->where('gateway_payment_ref', $paymentRef))
            ->latest()
            ->first();
    }

    private function finish(WebhookEvent $event, string $status, string $message): JsonResponse
    {
        $event->forceFill([
            'status' => $status,
            'processed_at' => now(),
            'error' => $status === WebhookEvent::STATUS_FAILED ? $message : null,
        ])->save();

        return response()->json(['status' => 'ok']);
    }
}
