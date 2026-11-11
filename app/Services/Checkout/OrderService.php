<?php

namespace App\Services\Checkout;

use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Buat order pending dan reservasi stok tiket secara atomik.
     *
     * @param  array{name: string, email: string, phone: string}  $buyer
     * @param  array<int, string>  $holderNames
     */
    public function create(
        Event $event,
        TicketType $ticketType,
        int $quantity,
        array $buyer,
        array $holderNames = [],
        ?string $idempotencyKey = null,
        ?User $user = null,
        ?int $expectedUnitPrice = null,
    ): Order {
        $email = mb_strtolower(trim($buyer['email']));

        if ($idempotencyKey) {
            $existing = Order::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing) {
                if (mb_strtolower($existing->buyer_email) !== $email) {
                    throw ValidationException::withMessages([
                        'idempotency_key' => 'Kunci checkout sudah dipakai untuk pesanan lain.',
                    ]);
                }

                return $existing->load(['items.ticketType', 'event']);
            }
        }

        $this->assertEventCanBeOrdered($event);
        $this->assertTicketCanBeOrdered($ticketType, $event, $quantity);
        $this->assertIdentityComplete($event, $quantity, $holderNames);
        $this->assertPendingOrderLimit($email, $user);

        try {
            return DB::transaction(function () use ($event, $ticketType, $quantity, $buyer, $email, $holderNames, $idempotencyKey, $user, $expectedUnitPrice): Order {
                $locked = TicketType::query()->whereKey($ticketType->getKey())->lockForUpdate()->first();

                if (! $locked) {
                    throw ValidationException::withMessages([
                        'ticket_type_id' => 'Jenis tiket tidak ditemukan.',
                    ]);
                }

                $this->assertTicketCanBeOrdered($locked, $event, $quantity);

                if ($expectedUnitPrice !== null && (int) $locked->price !== $expectedUnitPrice) {
                    throw ValidationException::withMessages([
                        'ticket_type_id' => 'Harga tiket berubah. Muat ulang halaman dan konfirmasi ulang harga terbaru.',
                    ]);
                }

                // Reservasi stok dengan kondisi pada query agar sold_count tidak
                // pernah melewati quota meski ada request bersamaan.
                $reserved = TicketType::query()
                    ->whereKey($locked->getKey())
                    ->whereRaw('quota - sold_count >= ?', [$quantity])
                    ->increment('sold_count', $quantity);

                if ($reserved !== 1) {
                    throw ValidationException::withMessages([
                        'ticket_type_id' => 'Tiket sudah habis. Pilih jenis tiket lain.',
                    ]);
                }

                $unitPrice = (int) $locked->price;
                $subtotal = $unitPrice * $quantity;
                $discount = 0;
                $adminFee = max(0, (int) config('ticketing.admin_fee', 0));
                $paymentFee = 0;
                $taxPercent = max(0, (float) config('ticketing.tax_percent', 0));
                $tax = (int) round(($subtotal - $discount + $adminFee) * $taxPercent / 100);
                $total = max(0, $subtotal - $discount + $adminFee + $paymentFee + $tax);

                $order = Order::query()->create([
                    'order_number' => $this->generateOrderNumber(),
                    'idempotency_key' => $idempotencyKey,
                    'user_id' => $user?->getKey(),
                    'event_id' => $event->getKey(),
                    'buyer_name' => trim($buyer['name']),
                    'buyer_email' => $email,
                    'buyer_phone' => trim($buyer['phone']),
                    'holder_names' => $this->normalizeHolderNames($holderNames),
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'admin_fee' => $adminFee,
                    'payment_fee' => $paymentFee,
                    'tax_amount' => $tax,
                    'total_amount' => $total,
                    'status' => Order::STATUS_PENDING,
                    'expires_at' => now()->addMinutes(max(1, (int) config('payment.order_expiry_minutes', 15))),
                ]);

                $order->items()->create([
                    'ticket_type_id' => $locked->getKey(),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                return $order->load(['items.ticketType', 'event']);
            });
        } catch (UniqueConstraintViolationException $exception) {
            if ($idempotencyKey && $existing = Order::query()->where('idempotency_key', $idempotencyKey)->first()) {
                return $existing->load(['items.ticketType', 'event']);
            }

            throw $exception;
        }
    }

    private function assertEventCanBeOrdered(Event $event): void
    {
        if (! $event->isOnSale() || $event->start_at?->isPast()) {
            throw ValidationException::withMessages([
                'event' => 'Event ini sudah tidak menerima pembelian tiket.',
            ]);
        }
    }

    private function assertTicketCanBeOrdered(TicketType $ticketType, Event $event, int $quantity): void
    {
        if ($ticketType->event_id !== $event->getKey()) {
            throw ValidationException::withMessages([
                'ticket_type_id' => 'Jenis tiket tidak sesuai dengan event.',
            ]);
        }

        if (! $ticketType->is_active) {
            throw ValidationException::withMessages([
                'ticket_type_id' => 'Jenis tiket ini sedang tidak dijual.',
            ]);
        }

        $min = max(1, (int) $ticketType->min_per_order);
        $max = max($min, (int) $ticketType->max_per_order);

        if ($quantity < $min) {
            throw ValidationException::withMessages([
                'quantity' => "Minimal pembelian {$min} tiket untuk jenis ini.",
            ]);
        }

        if ($quantity > $max) {
            throw ValidationException::withMessages([
                'quantity' => "Maksimal pembelian {$max} tiket untuk jenis ini.",
            ]);
        }

        if ($ticketType->sale_start_at?->isFuture()) {
            throw ValidationException::withMessages([
                'ticket_type_id' => 'Penjualan tiket ini belum dimulai.',
            ]);
        }

        if ($ticketType->sale_end_at?->isPast()) {
            throw ValidationException::withMessages([
                'ticket_type_id' => 'Masa penjualan tiket ini sudah berakhir.',
            ]);
        }

        if ($ticketType->remainingQuota() < $quantity) {
            throw ValidationException::withMessages([
                'ticket_type_id' => 'Sisa kuota tiket tidak mencukupi.',
            ]);
        }
    }

    /**
     * @param  array<int, string>  $holderNames
     */
    private function assertIdentityComplete(Event $event, int $quantity, array $holderNames): void
    {
        if (! $event->require_identity) {
            return;
        }

        $names = $this->normalizeHolderNames($holderNames) ?? [];

        if (count($names) !== $quantity || collect($names)->contains(fn (string $name) => $name === '')) {
            throw ValidationException::withMessages([
                'holder_names' => "Nama pemegang tiket wajib diisi untuk setiap tiket ({$quantity} nama).",
            ]);
        }
    }

    private function assertPendingOrderLimit(string $email, ?User $user): void
    {
        $limit = max(1, (int) config('ticketing.max_pending_orders_per_identity', 2));

        $pending = Order::query()
            ->pending()
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(function ($query) use ($email, $user): void {
                if ($user) {
                    $query->where('user_id', $user->getKey());
                }

                $query->orWhereRaw('LOWER(buyer_email) = ?', [$email]);
            })
            ->count();

        if ($pending >= $limit) {
            throw ValidationException::withMessages([
                'buyer_email' => "Terlalu banyak pesanan yang menunggu pembayaran. Selesaikan atau tunggu pesanan sebelumnya kedaluwarsa (maksimal {$limit} pesanan).",
            ]);
        }
    }

    /**
     * @param  array<int, string>  $holderNames
     * @return array<int, string>|null
     */
    private function normalizeHolderNames(array $holderNames): ?array
    {
        if ($holderNames === []) {
            return null;
        }

        return collect($holderNames)
            ->map(fn ($name) => trim((string) $name))
            ->values()
            ->all();
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }
}
