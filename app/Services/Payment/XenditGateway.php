<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Support\GuestOrderAccess;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Implementasi Xendit Payment Sessions API sebagai hosted payment page dan
 * Refund API v3 untuk pengembalian dana.
 *
 * Secret key dan callback token diambil dari config/payment.php. Token callback
 * bersifat statis (bukan HMAC) sehingga perbandingan wajib memakai hash_equals
 * dan gagal tertutup bila token belum dikonfigurasi.
 */
class XenditGateway implements PaymentGateway
{
    private const CURRENCY = 'IDR';

    private const COUNTRY = 'ID';

    /**
     * Xendit menolak expires_at yang kurang dari 10 menit di masa depan.
     */
    private const MIN_SESSION_MINUTES = 10;

    public function name(): string
    {
        return 'xendit';
    }

    public function supportedMethods(): array
    {
        return array_keys((array) config('payment.xendit.methods', []));
    }

    public function createCharge(Order $order, string $method): array
    {
        $this->ensureConfigured();

        $order->loadMissing('event');

        $channels = $this->channelsFor($method);
        $returnUrl = $this->httpsUrl(GuestOrderAccess::checkoutUrlFor($order));
        $expiresAt = $this->sessionExpiresAt($order);

        $response = $this->client()->post($this->baseUrl().'/sessions', array_filter([
            'reference_id' => $this->referenceId($order),
            'session_type' => 'PAY',
            'mode' => 'PAYMENT_LINK',
            'amount' => (int) $order->total_amount,
            'currency' => self::CURRENCY,
            'country' => self::COUNTRY,
            'expires_at' => $expiresAt->toIso8601String(),
            'description' => $this->description($order),
            'customer' => $this->customer($order),
            'allowed_payment_channels' => $channels,
            'success_return_url' => $returnUrl,
            'cancel_return_url' => $returnUrl,
            'metadata' => [
                'order_id' => (string) $order->getKey(),
                'order_number' => (string) $order->order_number,
            ],
        ], fn ($value) => $value !== null && $value !== []));

        if ($response->failed()) {
            throw new RuntimeException($this->failureMessage('Gagal membuat sesi pembayaran Xendit', $response->status(), (array) $response->json()));
        }

        $payload = (array) $response->json();
        $sessionId = (string) ($payload['payment_session_id'] ?? '');
        $paymentUrl = $payload['payment_link_url'] ?? null;

        if ($sessionId === '' || blank($paymentUrl)) {
            throw new RuntimeException('Respons sesi pembayaran Xendit tidak lengkap.');
        }

        return [
            'gateway_ref' => $sessionId,
            'payment_url' => (string) $paymentUrl,
            'expires_at' => filled($payload['expires_at'] ?? null)
                ? Carbon::parse((string) $payload['expires_at'])
                : $expiresAt,
            'raw' => $payload,
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $token = (string) config('payment.xendit.callback_token', '');

        if ($token === '') {
            return false;
        }

        return hash_equals($token, (string) $request->header('x-callback-token', ''));
    }

    public function parseWebhook(Request $request): array
    {
        $payload = $request->all();
        $event = (string) ($payload['event'] ?? '');
        $data = (array) ($payload['data'] ?? []);

        // Callback refund memakai bentuk { event, data } dan ditangani oleh
        // endpoint khusus. Jangan pernah memperlakukannya sebagai sesi bayar.
        if (str_starts_with($event, 'refund.')) {
            return [
                'external_id' => (string) ($data['refund_id'] ?? $data['id'] ?? ''),
                'payment_ref' => isset($data['payment_id']) ? (string) $data['payment_id'] : null,
                'event_type' => $event !== '' ? $event : null,
                'status' => Payment::STATUS_PENDING,
                'order_number' => null,
                'amount' => isset($data['amount']) ? (int) $data['amount'] : null,
                'currency' => $data['currency'] ?? null,
                'raw' => $payload,
            ];
        }

        return [
            'external_id' => (string) ($data['payment_session_id'] ?? ''),
            'payment_ref' => filled($data['payment_id'] ?? null) ? (string) $data['payment_id'] : null,
            'event_type' => $event !== ''
                ? $event
                : (filled($data['status'] ?? null) ? 'payment_session.'.mb_strtolower((string) $data['status']) : null),
            'status' => $this->mapStatus((string) ($data['status'] ?? ''), $event),
            // reference_id pada payload sesi adalah nomor order milik aplikasi.
            'order_number' => filled($data['reference_id'] ?? null) ? (string) $data['reference_id'] : null,
            'amount' => isset($data['amount']) ? (int) $data['amount'] : null,
            'currency' => $data['currency'] ?? null,
            'raw' => $payload,
        ];
    }

    public function refund(Payment $payment, int $amount, string $reason): array
    {
        $this->ensureConfigured();

        $payment->loadMissing('order');

        // Xendit tidak mendukung refund otomatis untuk Virtual Account.
        // Gagalkan dengan pesan yang bisa ditindaklanjuti admin, bukan
        // membiarkan request API ditolak tanpa konteks.
        if ($payment->method === 'bank_transfer') {
            return [
                'gateway_ref' => null,
                'status' => 'failed',
                'raw' => [],
                'message' => 'Xendit tidak mendukung refund otomatis untuk transfer bank/VA. Lakukan refund manual melalui dashboard Xendit.',
            ];
        }

        $paymentRef = (string) ($payment->gateway_payment_ref ?? '');

        if ($paymentRef === '') {
            throw new RuntimeException('Xendit payment_id belum tersedia untuk refund ini.');
        }

        $response = $this->refundClient()
            ->post($this->baseUrl().'/v3/refunds', array_filter([
                // Reference dapat diverifikasi kembali dari refund webhook.
                'reference_id' => $this->refundReference($payment),
                'payment_id' => $paymentRef,
                'amount' => $amount,
                'reason' => 'REQUESTED_BY_CUSTOMER',
                'metadata' => [
                    'order_number' => (string) $payment->order?->order_number,
                    'reason' => mb_substr($reason, 0, 500),
                ],
            ], fn ($value) => $value !== null && $value !== []));

        $payload = (array) ($response->json() ?? []);

        if ($response->failed()) {
            return [
                'gateway_ref' => null,
                'status' => 'failed',
                'raw' => $payload,
                'message' => $this->failureMessage('Xendit menolak permintaan refund', $response->status(), $payload),
            ];
        }

        return [
            'gateway_ref' => $payload['refund_id'] ?? null,
            'status' => $this->mapRefundStatus((string) ($payload['status'] ?? '')),
            'raw' => $payload,
        ];
    }

    public function getStatus(Payment $payment): string
    {
        $this->ensureConfigured();

        $sessionId = (string) ($payment->gateway_ref ?? '');

        if ($sessionId === '') {
            return (string) $payment->status;
        }

        $response = $this->client()->get($this->baseUrl().'/sessions/'.$sessionId);

        if ($response->failed()) {
            throw new RuntimeException('Gagal mengambil status sesi pembayaran Xendit ('.$response->status().').');
        }

        $payload = (array) ($response->json() ?? []);

        return $this->mapStatus((string) ($payload['status'] ?? ''));
    }

    public function getRefundStatus(Refund $refund): string
    {
        $this->ensureConfigured();

        $refundId = (string) ($refund->gateway_ref ?? '');

        if ($refundId === '') {
            return 'pending';
        }

        $response = $this->refundClient()->get($this->baseUrl().'/v3/refunds/'.$refundId);

        if ($response->failed()) {
            throw new RuntimeException('Gagal mengambil status refund Xendit ('.$response->status().').');
        }

        return $this->mapRefundStatus((string) ($response->json('status') ?? ''));
    }

    /**
     * @return array<int, string>
     */
    private function channelsFor(string $method): array
    {
        $channels = (array) config('payment.xendit.methods.'.$method, []);

        if ($channels === []) {
            throw new RuntimeException("Metode pembayaran [{$method}] tidak didukung Xendit.");
        }

        return array_values(array_map('strval', $channels));
    }

    private function referenceId(Order $order): string
    {
        $reference = trim((string) $order->order_number);

        if ($reference === '') {
            $reference = 'order-'.$order->getKey();
        }

        return mb_substr($reference, 0, 64);
    }

    /**
     * @return array<string, mixed>
     */
    private function customer(Order $order): array
    {
        $givenNames = trim((string) preg_replace('/\s+/', ' ', (string) preg_replace('/[^A-Za-z0-9 ]/', '', (string) $order->buyer_name)));

        if ($givenNames === '') {
            $givenNames = 'Pelanggan';
        }

        $customer = [
            // Xendit mewajibkan reference_id alfanumerik tanpa karakter khusus.
            'reference_id' => mb_substr('cust'.preg_replace('/[^A-Za-z0-9]/', '', (string) $order->order_number), 0, 255),
            'type' => 'INDIVIDUAL',
            'individual_detail' => [
                'given_names' => mb_substr($givenNames, 0, 255),
            ],
        ];

        $email = trim((string) $order->buyer_email);

        if ($email !== '' && mb_strlen($email) <= 50 && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $customer['email'] = $email;
        }

        $mobile = $this->mobileNumber((string) $order->buyer_phone);

        if ($mobile !== null) {
            $customer['mobile_number'] = $mobile;
        }

        return $customer;
    }

    private function mobileNumber(string $phone): ?string
    {
        $digits = (string) preg_replace('/\D/', '', $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        if (! str_starts_with($digits, '62') || mb_strlen($digits) < 10 || mb_strlen($digits) > 15) {
            return null;
        }

        return '+'.$digits;
    }

    private function description(Order $order): string
    {
        $eventTitle = $order->event?->title;

        return mb_substr(trim('Tiket '.$order->order_number.($eventTitle ? ' — '.$eventTitle : '')), 0, 1000);
    }

    private function refundReference(Payment $payment): string
    {
        return mb_substr('refund-'.($payment->order?->order_number ?: $payment->getKey()), 0, 255);
    }

    private function sessionExpiresAt(Order $order): Carbon
    {
        $duration = max(
            self::MIN_SESSION_MINUTES,
            (int) config('payment.xendit.session_duration_minutes', 15),
        );
        $minimum = now()->addMinutes(self::MIN_SESSION_MINUTES);
        $candidate = now()->addMinutes($duration);

        if ($order->expires_at) {
            if ($order->expires_at->lte($minimum)) {
                throw new RuntimeException('Batas pembayaran order terlalu dekat untuk sesi Xendit (minimum 10 menit).');
            }

            return $order->expires_at->lt($candidate)
                ? $order->expires_at->copy()->utc()
                : $candidate->utc();
        }

        return $candidate->utc();
    }

    private function httpsUrl(string $url): string
    {
        if (str_starts_with($url, 'http://')) {
            return 'https://'.substr($url, 7);
        }

        return $url;
    }

    private function mapStatus(string $status, string $event = ''): string
    {
        if ($event === 'payment_session.completed') {
            return Payment::STATUS_SUCCESS;
        }

        if ($event === 'payment_session.expired') {
            return Payment::STATUS_EXPIRED;
        }

        if ($event !== '') {
            return Payment::STATUS_PENDING;
        }

        return match (mb_strtoupper($status)) {
            'COMPLETED', 'PAID', 'SETTLED', 'SUCCEEDED' => Payment::STATUS_SUCCESS,
            'EXPIRED' => Payment::STATUS_EXPIRED,
            'CANCELED', 'CANCELLED', 'FAILED' => Payment::STATUS_FAILED,
            'REFUNDED' => Payment::STATUS_REFUNDED,
            default => Payment::STATUS_PENDING,
        };
    }

    private function mapRefundStatus(string $status): string
    {
        return match (mb_strtoupper($status)) {
            'SUCCEEDED' => 'succeeded',
            'PENDING' => 'pending',
            default => 'failed',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function failureMessage(string $prefix, int $status, array $payload): string
    {
        $detail = (string) ($payload['message'] ?? $payload['error_code'] ?? '');

        return $prefix.' ('.$status.')'.($detail !== '' ? ': '.$detail : '.');
    }

    private function client(): PendingRequest
    {
        return $this->request();
    }

    private function refundClient(): PendingRequest
    {
        return $this->request()->withHeaders([
            'api-version' => (string) config('payment.xendit.refund_api_version', '2024-11-11'),
        ]);
    }

    private function request(): PendingRequest
    {
        return Http::withBasicAuth((string) config('payment.xendit.secret_key'), '')
            ->acceptJson()
            ->asJson()
            ->timeout(20);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('payment.xendit.api_base_url', 'https://api.xendit.co'), '/');
    }

    private function ensureConfigured(): void
    {
        if (blank(config('payment.xendit.secret_key'))) {
            throw new RuntimeException('Xendit secret key belum dikonfigurasi.');
        }
    }
}
