<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Gateway palsu untuk test dan development tanpa jaringan.
 */
class FakeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function supportedMethods(): array
    {
        return array_keys((array) config('ticketing.payment_methods', []));
    }

    public function createCharge(Order $order, string $method): array
    {
        $ref = 'FAKE-'.strtoupper(Str::random(16));

        return [
            'gateway_ref' => $ref,
            'payment_url' => 'https://fake.payment.test/'.$ref,
            'expires_at' => $order->expires_at,
            'raw' => [
                'order_number' => $order->order_number,
                'method' => $method,
                'amount' => $order->total_amount,
            ],
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return $request->header('X-Fake-Signature') === $this->signature($request);
    }

    public function parseWebhook(Request $request): array
    {
        return [
            'external_id' => (string) $request->input('external_id', ''),
            'payment_ref' => null,
            'event_type' => $request->input('event_type'),
            'status' => (string) $request->input('status', 'pending'),
            'order_number' => $request->input('order_number'),
            'amount' => $request->filled('amount') ? (int) $request->input('amount') : null,
            'currency' => $request->input('currency'),
            'raw' => $request->all(),
        ];
    }

    public function refund(Payment $payment, int $amount, string $reason): array
    {
        return [
            'gateway_ref' => 'FAKE-REFUND-'.strtoupper(Str::random(12)),
            'status' => 'succeeded',
            'raw' => ['amount' => $amount, 'reason' => $reason],
        ];
    }

    public function getStatus(Payment $payment): string
    {
        return $payment->status;
    }

    public function getRefundStatus(Refund $refund): string
    {
        return $refund->status === Refund::STATUS_SUCCEEDED ? 'succeeded' : 'pending';
    }

    /**
     * Signature deterministik untuk payload fake.
     */
    public function signature(Request $request): string
    {
        return hash_hmac('sha256', $request->getContent(), (string) config('payment.fake.secret', 'fake-secret'));
    }
}
