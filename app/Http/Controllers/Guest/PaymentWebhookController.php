<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Services\Checkout\PaymentConfirmationService;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PaymentWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $gateway,
        PaymentManager $payments,
        PaymentConfirmationService $confirmation,
    ): JsonResponse {
        try {
            $driver = $payments->gateway($gateway);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        $payload = $driver->parseWebhook($request);
        $signatureValid = $driver->verifyWebhookSignature($request);
        $externalId = (string) ($payload['external_id'] ?? '');

        if ($externalId === '') {
            $externalId = 'unknown-'.hash('sha256', $request->getContent());
        }

        $event = WebhookEvent::query()->firstOrCreate(
            ['gateway' => $driver->name(), 'external_id' => $externalId],
            [
                'event_type' => $payload['event_type'] ?? null,
                'payload' => $payload['raw'] ?? [],
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

        $orderNumber = (string) ($payload['order_number'] ?? '');
        $order = $orderNumber !== '' ? Order::query()->where('order_number', $orderNumber)->first() : null;

        if (! $order) {
            return $this->finish($event, WebhookEvent::STATUS_FAILED, 'Order tidak ditemukan.');
        }

        $status = mb_strtolower((string) ($payload['status'] ?? ''));

        if ($status === Payment::STATUS_SUCCESS) {
            $payment = Payment::query()
                ->where('gateway', $driver->name())
                ->where('gateway_ref', $externalId)
                ->first()
                ?? $order->payments()->where('status', Payment::STATUS_PENDING)->latest()->first();

            if (! $payment) {
                $payment = $order->payments()->create([
                    'gateway' => $driver->name(),
                    'method' => (string) ($payload['event_type'] ?? 'gateway'),
                    'gateway_ref' => $externalId,
                    'amount' => (int) $order->total_amount,
                    'status' => Payment::STATUS_PENDING,
                    'raw_response' => $payload['raw'] ?? [],
                ]);
            }

            $result = $confirmation->confirm($order, $payment, $payload['amount'] ?? null);

            if ($result === PaymentConfirmationService::RESULT_AMOUNT_MISMATCH) {
                return $this->finish($event, WebhookEvent::STATUS_FAILED, 'Nominal pembayaran tidak sesuai.');
            }

            return $this->finish($event, WebhookEvent::STATUS_PROCESSED, 'Pembayaran diproses: '.$result);
        }

        if (in_array($status, [Payment::STATUS_FAILED, Payment::STATUS_EXPIRED], true)) {
            if ($order->status !== Order::STATUS_PAID) {
                $payment = Payment::query()
                    ->where('gateway', $driver->name())
                    ->where('gateway_ref', $externalId)
                    ->first();

                if ($payment) {
                    $payment->forceFill([
                        'status' => $status,
                        'paid_at' => null,
                    ])->save();
                }
            }

            return $this->finish($event, WebhookEvent::STATUS_PROCESSED, 'Status pembayaran diperbarui.');
        }

        return $this->finish($event, WebhookEvent::STATUS_IGNORED, 'Status tidak memengaruhi order.');
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
