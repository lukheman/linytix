<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Implementasi Midtrans Snap untuk sandbox.
 *
 * Kredensial diambil dari config/payment.php. Jangan pernah menulis server key
 * ke log atau response.
 */
class MidtransGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'midtrans';
    }

    public function supportedMethods(): array
    {
        return array_keys((array) config('ticketing.payment_methods', []));
    }

    public function createCharge(Order $order, string $method): array
    {
        $this->ensureConfigured();

        $response = Http::withBasicAuth((string) config('payment.midtrans.server_key'), '')
            ->acceptJson()
            ->post(rtrim((string) config('payment.midtrans.snap_base_url'), '/').'/transactions', [
                'transaction_details' => [
                    'order_id' => $order->order_number,
                    'gross_amount' => $order->total_amount,
                ],
                'customer_details' => [
                    'first_name' => $order->buyer_name,
                    'email' => $order->buyer_email,
                    'phone' => $order->buyer_phone,
                ],
                'enabled_payments' => [$method],
                'expiry' => [
                    'start_time' => now()->format('Y-m-d H:i:s O'),
                    'unit' => 'minutes',
                    'duration' => max(1, (int) config('payment.order_expiry_minutes', 15)),
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gagal membuat transaksi Midtrans.');
        }

        $payload = $response->json();

        return [
            'gateway_ref' => (string) ($payload['transaction_id'] ?? Str::uuid()),
            'payment_url' => $payload['redirect_url'] ?? null,
            'expires_at' => $order->expires_at,
            'raw' => $payload,
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $this->ensureConfigured();

        $expected = hash('sha512', implode('', [
            (string) $request->input('order_id'),
            (string) $request->input('status_code'),
            (string) $request->input('gross_amount'),
            (string) config('payment.midtrans.server_key'),
        ]));

        return hash_equals($expected, (string) $request->input('signature_key'));
    }

    public function parseWebhook(Request $request): array
    {
        return [
            'external_id' => (string) $request->input('transaction_id', ''),
            'payment_ref' => null,
            'event_type' => $request->input('transaction_status'),
            'status' => $this->mapStatus((string) $request->input('transaction_status')),
            'order_number' => $request->input('order_id'),
            'amount' => $request->filled('gross_amount') ? (int) $request->input('gross_amount') : null,
            'currency' => $request->input('currency'),
            'raw' => $request->all(),
        ];
    }

    public function refund(Payment $payment, int $amount, string $reason): array
    {
        $this->ensureConfigured();

        $response = Http::withBasicAuth((string) config('payment.midtrans.server_key'), '')
            ->acceptJson()
            ->post(rtrim((string) config('payment.midtrans.api_base_url'), '/').'/v2/'.$payment->gateway_ref.'/refund', [
                'refund_key' => (string) Str::uuid(),
                'amount' => $amount,
                'reason' => $reason,
            ]);

        if ($response->failed()) {
            return ['gateway_ref' => null, 'status' => 'failed', 'raw' => $response->json() ?? []];
        }

        return [
            'gateway_ref' => $response->json('refund_key'),
            'status' => 'succeeded',
            'raw' => $response->json() ?? [],
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

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'capture', 'settlement' => Payment::STATUS_SUCCESS,
            'deny', 'cancel', 'failure' => Payment::STATUS_FAILED,
            'expire' => Payment::STATUS_EXPIRED,
            'refund', 'partial_refund' => Payment::STATUS_REFUNDED,
            default => Payment::STATUS_PENDING,
        };
    }

    private function ensureConfigured(): void
    {
        if (blank(config('payment.midtrans.server_key'))) {
            throw new RuntimeException('Midtrans server key belum dikonfigurasi.');
        }
    }
}
