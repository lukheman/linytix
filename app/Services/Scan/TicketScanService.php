<?php

namespace App\Services\Scan;

use App\Models\AuditLog;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TicketScanService
{
    public const RESULT_SUCCESS = 'success';

    public const RESULT_DUPLICATE = 'duplicate';

    public const RESULT_INVALID = 'invalid';

    /**
     * Validasi dan proses scan tiket.
     *
     * Validitas sepenuhnya ditentukan di server berdasarkan `ticket_code`,
     * event yang sedang dibuka, status order, dan status tiket.
     *
     * @return array<string, mixed>
     */
    public function scan(
        Event $event,
        string $code,
        ?User $scanner = null,
        ?string $gate = null,
        ?string $ip = null,
    ): array {
        $code = trim($code);

        $ticket = Ticket::query()
            ->with(['order', 'ticketType'])
            ->where('ticket_code', $code)
            ->first();

        if (! $ticket) {
            return $this->reject($event, null, $scanner, $gate, $code, 'not_found', 'Kode tiket tidak ditemukan.', $ip);
        }

        if ($ticket->event_id !== $event->getKey()) {
            return $this->reject($event, $ticket, $scanner, $gate, $code, 'wrong_event', 'Tiket ini bukan untuk event yang sedang discan.', $ip);
        }

        if ($event->status === Event::STATUS_CANCELLED) {
            return $this->reject($event, $ticket, $scanner, $gate, $code, 'event_cancelled', 'Event ini sudah dibatalkan.', $ip);
        }

        if ($ticket->order?->status !== Order::STATUS_PAID) {
            return $this->reject($event, $ticket, $scanner, $gate, $code, 'order_not_paid', 'Pembayaran tiket belum lunas atau order tidak aktif.', $ip);
        }

        if (in_array($ticket->status, [Ticket::STATUS_REFUNDED, Ticket::STATUS_CANCELLED], true)) {
            return $this->reject($event, $ticket, $scanner, $gate, $code, 'ticket_inactive', 'Tiket sudah dibatalkan atau direfund.', $ip);
        }

        if ($ticket->status === Ticket::STATUS_USED) {
            return $this->duplicate($event, $ticket, $scanner, $gate);
        }

        if ($ticket->status !== Ticket::STATUS_ACTIVE) {
            return $this->reject($event, $ticket, $scanner, $gate, $code, 'ticket_inactive', 'Tiket tidak dapat digunakan.', $ip);
        }

        $checkIn = DB::transaction(function () use ($event, $ticket, $scanner, $gate): ?CheckIn {
            $now = now();

            // Hanya satu scan yang boleh mengubah status active -> used.
            $updated = Ticket::query()
                ->whereKey($ticket->getKey())
                ->where('status', Ticket::STATUS_ACTIVE)
                ->update([
                    'status' => Ticket::STATUS_USED,
                    'used_at' => $now,
                ]);

            if ($updated === 0) {
                return null;
            }

            return $this->recordCheckIn($event, $ticket, $scanner, $gate, self::RESULT_SUCCESS, $now);
        });

        if (! $checkIn) {
            return $this->duplicate($event, $ticket->refresh(), $scanner, $gate);
        }

        return [
            'result' => self::RESULT_SUCCESS,
            'title' => 'Tiket Valid',
            'message' => 'Tiket valid. Persilakan pemegang tiket masuk.',
            'ticket' => $this->ticketPayload($ticket),
            'gate' => $gate,
            'checked_in_at' => $checkIn->scanned_at?->toIso8601String(),
            'checked_in_at_label' => $this->label($checkIn->scanned_at, $event),
            'previous' => null,
            'reason' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function duplicate(Event $event, Ticket $ticket, ?User $scanner, ?string $gate): array
    {
        $first = CheckIn::query()
            ->where('ticket_id', $ticket->getKey())
            ->where('result', self::RESULT_SUCCESS)
            ->orderBy('scanned_at')
            ->first();

        $this->recordCheckIn($event, $ticket, $scanner, $gate, self::RESULT_DUPLICATE);

        $previousAt = $first?->scanned_at ?? $ticket->used_at;
        $previousGate = $first?->gate;

        $message = 'Tiket sudah digunakan';

        if ($previousAt) {
            $message .= ' pada '.$this->label($previousAt, $event);
        }

        if ($previousGate) {
            $message .= ' di '.$previousGate;
        }

        return [
            'result' => self::RESULT_DUPLICATE,
            'title' => 'Tiket Sudah Digunakan',
            'message' => $message.'.',
            'ticket' => $this->ticketPayload($ticket),
            'gate' => $gate,
            'checked_in_at' => null,
            'checked_in_at_label' => null,
            'previous' => [
                'checked_in_at' => $previousAt?->toIso8601String(),
                'checked_in_at_label' => $this->label($previousAt, $event),
                'gate' => $previousGate,
            ],
            'reason' => 'already_used',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reject(
        Event $event,
        ?Ticket $ticket,
        ?User $scanner,
        ?string $gate,
        string $code,
        string $reason,
        string $message,
        ?string $ip,
    ): array {
        if ($ticket) {
            $this->recordCheckIn($event, $ticket, $scanner, $gate, self::RESULT_INVALID);
        }

        AuditLog::query()->create([
            'user_id' => $scanner?->getKey(),
            'action' => 'ticket.scan.invalid',
            'entity' => 'event',
            'entity_id' => $event->getKey(),
            'meta' => [
                'reason' => $reason,
                'ticket_id' => $ticket?->getKey(),
                'ticket_code' => $code,
            ],
            'ip_address' => $ip,
        ]);

        return [
            'result' => self::RESULT_INVALID,
            'title' => 'Tiket Tidak Valid',
            'message' => $message,
            'ticket' => null,
            'gate' => $gate,
            'checked_in_at' => null,
            'checked_in_at_label' => null,
            'previous' => null,
            'reason' => $reason,
        ];
    }

    private function recordCheckIn(
        Event $event,
        Ticket $ticket,
        ?User $scanner,
        ?string $gate,
        string $result,
        mixed $scannedAt = null,
    ): CheckIn {
        return CheckIn::query()->create([
            'ticket_id' => $ticket->getKey(),
            'event_id' => $event->getKey(),
            'scanned_by' => $scanner?->getKey(),
            'gate' => $gate,
            'result' => $result,
            'scanned_at' => $scannedAt ?? now(),
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function ticketPayload(Ticket $ticket): array
    {
        return [
            'ticket_code' => $ticket->ticket_code,
            'holder_name' => $ticket->holder_name,
            'ticket_type' => $ticket->ticketType?->name,
        ];
    }

    private function label(mixed $at, Event $event): ?string
    {
        if (! $at) {
            return null;
        }

        return $at->copy()
            ->setTimezone($event->timezoneLabel())
            ->translatedFormat('d M Y, H.i');
    }
}
