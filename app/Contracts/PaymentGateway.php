<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /**
     * Nama gateway yang disimpan pada tabel payments.
     */
    public function name(): string;

    /**
     * Buat transaksi pembayaran untuk order.
     *
     * @return array{gateway_ref: string, payment_url: ?string, expires_at: ?\DateTimeInterface, raw: array<string, mixed>}
     */
    public function createCharge(Order $order, string $method): array;

    /**
     * Validasi signature webhook. Implementasi wajib menolak payload tanpa signature valid.
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Normalisasi payload webhook ke bentuk internal.
     *
     * @return array{external_id: string, event_type: ?string, status: string, order_number: ?string, amount: ?int, raw: array<string, mixed>}
     */
    public function parseWebhook(Request $request): array;

    /**
     * Kirim permintaan refund ke gateway.
     *
     * @return array{gateway_ref: ?string, status: string, raw: array<string, mixed>}
     */
    public function refund(Payment $payment, int $amount, string $reason): array;

    /**
     * Ambil status terkini pembayaran dari gateway.
     */
    public function getStatus(Payment $payment): string;
}
