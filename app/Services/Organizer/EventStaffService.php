<?php

namespace App\Services\Organizer;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventStaffService
{
    /**
     * Tambahkan atau perbarui penugasan staff pada sebuah event.
     *
     * Pengguna otomatis mendapat role `staff` agar dapat melewati middleware
     * area scan, tetapi akses tetap dibatasi oleh penugasan event.
     */
    public function assign(
        Event $event,
        User $staff,
        ?string $gate,
        User $actor,
        ?string $ip = null,
    ): User {
        if ($event->organizer?->owner_user_id === $staff->getKey()) {
            throw ValidationException::withMessages([
                'email' => 'Pemilik organizer tidak perlu ditugaskan sebagai staff event ini.',
            ]);
        }

        DB::transaction(function () use ($event, $staff, $gate, $actor, $ip): void {
            $gate = filled($gate) ? trim((string) $gate) : null;
            $existing = $event->staff()->whereKey($staff->getKey())->exists();

            if ($existing) {
                $event->staff()->updateExistingPivot($staff->getKey(), ['gate' => $gate]);
            } else {
                $event->staff()->attach($staff->getKey(), ['gate' => $gate]);
            }

            if (! $staff->isStaff()) {
                $staff->assignRole(Role::STAFF);
            }

            AuditLog::query()->create([
                'user_id' => $actor->getKey(),
                'action' => 'event.staff.assigned',
                'entity' => 'event',
                'entity_id' => $event->getKey(),
                'meta' => [
                    'staff_id' => $staff->getKey(),
                    'staff_email' => $staff->email,
                    'gate' => $gate,
                ],
                'ip_address' => $ip,
            ]);
        });

        return $staff->refresh();
    }

    /**
     * Lepas penugasan staff dari event. Role staff tidak dicabut karena
     * pengguna mungkin masih bertugas pada event lain.
     */
    public function remove(Event $event, User $staff, User $actor, ?string $ip = null): void
    {
        DB::transaction(function () use ($event, $staff, $actor, $ip): void {
            $assignment = $event->staff()->whereKey($staff->getKey())->first();

            if (! $assignment) {
                return;
            }

            $event->staff()->detach($staff->getKey());

            AuditLog::query()->create([
                'user_id' => $actor->getKey(),
                'action' => 'event.staff.removed',
                'entity' => 'event',
                'entity_id' => $event->getKey(),
                'meta' => [
                    'staff_id' => $staff->getKey(),
                    'staff_email' => $staff->email,
                    'gate' => $assignment->pivot?->gate,
                ],
                'ip_address' => $ip,
            ]);
        });
    }
}
