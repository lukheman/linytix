<?php

namespace App\Services\Refund;

use App\Jobs\ProcessRefund;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\EventCancelled;
use App\Notifications\RefundSucceeded;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

class RefundService
{
    /**
     * Buat refund penuh untuk order paid. Idempoten: pemanggilan ulang
     * mengembalikan refund yang sudah ada tanpa mengirim ulang ke gateway.
     */
    public function requestFullRefund(Order $order, User $admin, string $reason, ?string $ip = null): Refund
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Alasan refund wajib diisi.',
            ]);
        }

        $refund = DB::transaction(function () use ($order, $admin, $reason, $ip): Refund {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

            $existing = Refund::query()
                ->where('order_id', $order->getKey())
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first();

            // Refund yang sudah ada (termasuk yang gagal) dipakai ulang agar
            // klik ganda tidak membuat record atau panggilan gateway baru.
            if ($existing) {
                return $existing;
            }

            if (! $locked || $locked->status !== Order::STATUS_PAID) {
                throw ValidationException::withMessages([
                    'order' => 'Hanya pesanan berstatus lunas yang dapat direfund.',
                ]);
            }

            $payment = $locked->payments()
                ->where('status', Payment::STATUS_SUCCESS)
                ->latest('paid_at')
                ->first()
                ?? $locked->payments()->latest()->first();

            if (! $payment) {
                throw ValidationException::withMessages([
                    'order' => 'Pesanan ini tidak memiliki data pembayaran yang dapat direfund.',
                ]);
            }

            $refund = Refund::query()->create([
                'order_id' => $locked->getKey(),
                'payment_id' => $payment->getKey(),
                'amount' => (int) $locked->total_amount,
                'reason' => $reason,
                'status' => Refund::STATUS_REQUESTED,
                'requested_by' => $admin->getKey(),
            ]);

            $this->audit($admin, 'refund.requested', 'refund', $refund->getKey(), [
                'order_number' => $locked->order_number,
                'payment_id' => $payment->getKey(),
                'amount' => (int) $refund->amount,
                'reason' => $reason,
            ], $ip);

            return $refund;
        });

        if ($refund->wasRecentlyCreated) {
            ProcessRefund::dispatch($refund->getKey(), $admin->getKey());
        }

        return $refund;
    }

    /**
     * Proses ulang refund yang gagal atau masih menunggu.
     */
    public function retry(Refund $refund, User $admin, ?string $ip = null): Refund
    {
        $previousStatus = $refund->status;

        DB::transaction(function () use ($refund, $admin, $ip, $previousStatus): void {
            $locked = Refund::query()->whereKey($refund->getKey())->lockForUpdate()->first();

            if (! $locked || ! in_array($locked->status, [Refund::STATUS_REQUESTED, Refund::STATUS_FAILED], true)) {
                throw ValidationException::withMessages([
                    'refund' => 'Refund ini tidak dapat diproses ulang pada status saat ini.',
                ]);
            }

            $locked->forceFill([
                'status' => Refund::STATUS_REQUESTED,
                'error' => null,
            ])->save();

            $this->audit($admin, 'refund.retried', 'refund', $locked->getKey(), [
                'order_number' => $locked->order?->order_number,
                'previous_status' => $previousStatus,
            ], $ip);
        });

        ProcessRefund::dispatch($refund->getKey(), $admin->getKey());

        return $refund->refresh();
    }

    /**
     * Batalkan event dan buat refund untuk seluruh order paid.
     *
     * @return array{orders: int, created: int, existing: int, failed: int}
     */
    public function cancelEvent(Event $event, User $admin, string $reason, ?string $ip = null): array
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Alasan pembatalan event wajib diisi.',
            ]);
        }

        if (! in_array($event->status, [Event::STATUS_PUBLISHED, Event::STATUS_SOLD_OUT], true)) {
            throw ValidationException::withMessages([
                'event_id' => 'Hanya event berstatus Published atau Sold Out yang dapat dibatalkan.',
            ]);
        }

        DB::transaction(function () use ($event, $admin, $reason, $ip): void {
            $locked = Event::query()->whereKey($event->getKey())->lockForUpdate()->first();

            if (! $locked || ! in_array($locked->status, [Event::STATUS_PUBLISHED, Event::STATUS_SOLD_OUT], true)) {
                throw ValidationException::withMessages([
                    'event_id' => 'Hanya event berstatus Published atau Sold Out yang dapat dibatalkan.',
                ]);
            }

            $locked->forceFill(['status' => Event::STATUS_CANCELLED])->save();

            $this->audit($admin, 'event.cancelled', 'event', $locked->getKey(), [
                'title' => $locked->title,
                'reason' => $reason,
            ], $ip);
        });

        $orders = $event->orders()
            ->where('status', Order::STATUS_PAID)
            ->get();

        $created = 0;
        $existing = 0;
        $failed = 0;

        foreach ($orders as $order) {
            try {
                $refund = $this->requestFullRefund($order, $admin, $reason, $ip);

                if ($refund->wasRecentlyCreated) {
                    $created++;
                } else {
                    $existing++;
                }
            } catch (ValidationException) {
                $failed++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        $this->audit($admin, 'refund.batch_requested', 'event', $event->getKey(), [
            'orders' => $orders->count(),
            'created' => $created,
            'existing' => $existing,
            'failed' => $failed,
            'reason' => $reason,
        ], $ip);

        $event->loadMissing('organizer.owner');
        $organizerEmail = $event->organizer?->owner?->email
            ?? $event->organizer?->contact_email;

        if (filled($organizerEmail)) {
            Notification::route('mail', $organizerEmail)
                ->notify(new EventCancelled($event, $reason));
        }

        return [
            'orders' => $orders->count(),
            'created' => $created,
            'existing' => $existing,
            'failed' => $failed,
        ];
    }

    /**
     * Tandai refund sukses, lalu refund order, tiket, dan pembayaran.
     * Stok tiket sengaja tidak dikembalikan untuk mencegah overselling.
     */
    public function markSucceeded(Refund $refund, ?string $gatewayRef = null): void
    {
        $notifiedOrder = null;
        $notifiedRefund = null;

        DB::transaction(function () use ($refund, $gatewayRef, &$notifiedOrder, &$notifiedRefund): void {
            $locked = Refund::query()->whereKey($refund->getKey())->lockForUpdate()->first();

            if (! $locked || $locked->status === Refund::STATUS_SUCCEEDED) {
                return;
            }

            $locked->forceFill([
                'status' => Refund::STATUS_SUCCEEDED,
                'gateway_ref' => $gatewayRef ?: $locked->gateway_ref,
                'error' => null,
                'processed_at' => now(),
            ])->save();

            $order = Order::query()->whereKey($locked->order_id)->lockForUpdate()->first();

            if ($order) {
                $order->forceFill(['status' => Order::STATUS_REFUNDED])->save();

                Ticket::query()
                    ->where('order_id', $order->getKey())
                    ->update(['status' => Ticket::STATUS_REFUNDED]);

                $locked->payment?->forceFill(['status' => Payment::STATUS_REFUNDED])->save();

                $notifiedOrder = $order;
                $notifiedRefund = $locked;
            }

            $this->audit($locked->processor, 'refund.succeeded', 'refund', $locked->getKey(), [
                'order_number' => $order?->order_number,
                'amount' => (int) $locked->amount,
                'gateway_ref' => $locked->gateway_ref,
            ]);
        });

        if ($notifiedOrder && $notifiedRefund) {
            try {
                Notification::route('mail', $notifiedOrder->buyer_email)
                    ->notify(new RefundSucceeded($notifiedOrder->loadMissing('event'), $notifiedRefund));
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    /**
     * Tandai refund gagal agar terlihat dan dapat di-retry dari admin.
     */
    public function markFailed(Refund $refund, string $error): void
    {
        DB::transaction(function () use ($refund, $error): void {
            $locked = Refund::query()->whereKey($refund->getKey())->lockForUpdate()->first();

            if (! $locked || $locked->status === Refund::STATUS_SUCCEEDED) {
                return;
            }

            $locked->forceFill([
                'status' => Refund::STATUS_FAILED,
                'error' => mb_substr($error, 0, 2000),
                'processed_at' => now(),
            ])->save();

            $this->audit($locked->processor, 'refund.failed', 'refund', $locked->getKey(), [
                'order_number' => $locked->order?->order_number,
                'amount' => (int) $locked->amount,
                'error' => mb_substr($error, 0, 500),
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function audit(?User $user, string $action, string $entity, ?string $entityId, array $meta = [], ?string $ip = null): void
    {
        AuditLog::query()->create([
            'user_id' => $user?->getKey(),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'meta' => $meta,
            'ip_address' => $ip,
        ]);
    }
}
