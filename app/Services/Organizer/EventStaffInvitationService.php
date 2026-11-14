<?php

namespace App\Services\Organizer;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventStaffInvitation;
use App\Models\User;
use App\Notifications\StaffAssigned;
use App\Notifications\StaffInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EventStaffInvitationService
{
    public function __construct(private EventStaffService $staff) {}

    /**
     * Undang staff berdasarkan email.
     *
     * Jika akun aktif sudah ada, pengguna langsung ditugaskan dan menerima
     * email pemberitahuan. Jika belum ada, dibuat undangan berbatas waktu.
     *
     * @return array{type: 'assigned'|'invited', user: ?User, invitation: ?EventStaffInvitation}
     */
    public function invite(
        Event $event,
        string $email,
        string $gate,
        User $actor,
        ?string $ip = null,
    ): array {
        $email = mb_strtolower(trim($email));
        $gate = trim($gate);

        if ($event->status === Event::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'email' => 'Event yang dibatalkan tidak dapat menerima staff.',
            ]);
        }

        $event->loadMissing('organizer');

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($user) {
            return $this->assignExistingUser($event, $user, $gate, $actor, $ip);
        }

        [$invitation, $rawToken] = DB::transaction(function () use ($event, $email, $gate, $actor, $ip): array {
            $pending = EventStaffInvitation::query()
                ->where('event_id', $event->getKey())
                ->where('email', $email)
                ->pending()
                ->lockForUpdate()
                ->first();

            if ($pending) {
                throw ValidationException::withMessages([
                    'email' => 'Undangan untuk email ini masih aktif. Gunakan tombol Kirim Ulang.',
                ]);
            }

            $rawToken = Str::random(64);

            $invitation = EventStaffInvitation::query()->create([
                'event_id' => $event->getKey(),
                'email' => $email,
                'gate' => $gate,
                'token_hash' => EventStaffInvitation::hashToken($rawToken),
                'invited_by' => $actor->getKey(),
                'expires_at' => now()->addDays($this->ttlDays()),
                'last_sent_at' => now(),
                'send_count' => 1,
            ]);

            $this->audit($actor, 'event.staff.invited', $event, [
                'invitation_id' => $invitation->getKey(),
                'email' => $email,
                'gate' => $gate,
            ], $ip);

            return [$invitation, $rawToken];
        });

        $this->sendInvitation($invitation, $rawToken);

        return ['type' => 'invited', 'user' => null, 'invitation' => $invitation];
    }

    /**
     * Kirim ulang undangan dengan token baru. Token lama otomatis tidak berlaku.
     */
    public function resend(
        EventStaffInvitation $invitation,
        User $actor,
        ?string $ip = null,
    ): EventStaffInvitation {
        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'email' => 'Undangan ini sudah tidak berlaku.',
            ]);
        }

        $cooldown = $this->resendCooldownSeconds();

        if ($cooldown > 0 && $invitation->last_sent_at) {
            $elapsed = (int) $invitation->last_sent_at->diffInSeconds(now());

            if ($elapsed < $cooldown) {
                throw ValidationException::withMessages([
                    'email' => 'Tunggu '.max(1, $cooldown - $elapsed).' detik sebelum mengirim ulang undangan.',
                ]);
            }
        }

        $rawToken = Str::random(64);

        DB::transaction(function () use ($invitation, $actor, $ip, $rawToken): void {
            $invitation->forceFill([
                'token_hash' => EventStaffInvitation::hashToken($rawToken),
                'expires_at' => now()->addDays($this->ttlDays()),
                'last_sent_at' => now(),
                'send_count' => $invitation->send_count + 1,
            ])->save();

            $this->audit($actor, 'event.staff.invitation_resent', $invitation->event, [
                'invitation_id' => $invitation->getKey(),
                'email' => $invitation->email,
                'gate' => $invitation->gate,
                'send_count' => $invitation->send_count,
            ], $ip);
        });

        $invitation->refresh();
        $this->sendInvitation($invitation, $rawToken);

        return $invitation;
    }

    /**
     * Batalkan undangan yang masih menunggu.
     */
    public function revoke(
        EventStaffInvitation $invitation,
        User $actor,
        ?string $ip = null,
    ): void {
        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages([
                'invitation' => 'Undangan sudah diterima dan tidak dapat dibatalkan.',
            ]);
        }

        if ($invitation->isRevoked()) {
            return;
        }

        DB::transaction(function () use ($invitation, $actor, $ip): void {
            $invitation->forceFill(['revoked_at' => now()])->save();

            $this->audit($actor, 'event.staff.invitation_revoked', $invitation->event, [
                'invitation_id' => $invitation->getKey(),
                'email' => $invitation->email,
                'gate' => $invitation->gate,
            ], $ip);
        });
    }

    /**
     * Terima undangan dan tugaskan pengguna pada event.
     */
    public function accept(
        EventStaffInvitation $invitation,
        User $user,
        ?string $ip = null,
    ): void {
        DB::transaction(function () use ($invitation, $user, $ip): void {
            $locked = EventStaffInvitation::query()
                ->whereKey($invitation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'invitation' => 'Undangan ini sudah tidak berlaku.',
                ]);
            }

            if ($locked->event->status === Event::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'invitation' => 'Event ini sudah dibatalkan.',
                ]);
            }

            if (mb_strtolower($locked->email) !== mb_strtolower($user->email)) {
                throw ValidationException::withMessages([
                    'invitation' => 'Undangan ini ditujukan untuk email lain.',
                ]);
            }

            if ($user->is_active === false) {
                throw ValidationException::withMessages([
                    'invitation' => 'Akun Anda tidak aktif.',
                ]);
            }

            $this->staff->assign($locked->event, $user, $locked->gate, $user, $ip);

            $locked->forceFill([
                'accepted_at' => now(),
                'accepted_by' => $user->getKey(),
            ])->save();

            $this->audit($user, 'event.staff.invitation_accepted', $locked->event, [
                'invitation_id' => $locked->getKey(),
                'email' => $locked->email,
                'gate' => $locked->gate,
            ], $ip);
        });

        $invitation->refresh();
    }

    /**
     * @return array{type: 'assigned', user: User, invitation: null}
     */
    private function assignExistingUser(
        Event $event,
        User $user,
        string $gate,
        User $actor,
        ?string $ip,
    ): array {
        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Pengguna tersebut tidak aktif.',
            ]);
        }

        if ($event->organizer?->owner_user_id === $user->getKey()) {
            throw ValidationException::withMessages([
                'email' => 'Pemilik organizer tidak perlu ditugaskan sebagai staff event ini.',
            ]);
        }

        $existing = $event->staff()->whereKey($user->getKey())->first();

        if ($existing && ($existing->pivot?->gate ?? null) === $gate) {
            return ['type' => 'assigned', 'user' => $user, 'invitation' => null];
        }

        $this->staff->assign($event, $user, $gate, $actor, $ip);

        $user->notify(new StaffAssigned($event, $gate));

        return ['type' => 'assigned', 'user' => $user, 'invitation' => null];
    }

    private function sendInvitation(EventStaffInvitation $invitation, string $rawToken): void
    {
        $invitation->loadMissing(['event.organizer', 'event.venue']);

        try {
            Notification::route('mail', $invitation->email)
                ->notify(new StaffInvitation(
                    $invitation,
                    route('staff.invitations.show', $rawToken),
                ));
        } catch (\Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'email' => 'Undangan tersimpan, tetapi email gagal dikirim. Gunakan tombol Kirim Ulang.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function audit(User $actor, string $action, Event $event, array $meta, ?string $ip): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->getKey(),
            'action' => $action,
            'entity' => 'event',
            'entity_id' => $event->getKey(),
            'meta' => $meta,
            'ip_address' => $ip,
        ]);
    }

    private function ttlDays(): int
    {
        return max(1, (int) config('ticketing.staff_invitation_ttl_days', 7));
    }

    private function resendCooldownSeconds(): int
    {
        return max(0, (int) config('ticketing.staff_invitation_resend_cooldown_seconds', 60));
    }
}
