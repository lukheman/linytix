<?php

namespace App\Services\Organizer;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\OrganizerSlugRedirect;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizerService
{
    /**
     * Profil organizer milik user, dibuat otomatis saat pertama dibutuhkan.
     */
    public function forUser(User $user): Organizer
    {
        $organizer = Organizer::query()
            ->where('owner_user_id', $user->getKey())
            ->first();

        if ($organizer) {
            return $organizer;
        }

        return Organizer::query()->create([
            'owner_user_id' => $user->getKey(),
            'name' => $user->name,
            'slug' => $this->uniqueSlug($user->name),
            'contact_email' => $user->email,
        ]);
    }

    /**
     * Slug unik terhadap slug aktif dan riwayat slug organizer lain.
     */
    public function uniqueSlug(string $name, ?string $ignoreOrganizerId = null): string
    {
        $base = Str::slug($name) ?: 'organizer';
        $slug = $base;
        $counter = 2;

        while ($this->slugExists($slug, $ignoreOrganizerId)) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Organizer $organizer, User $actor, array $data, ?string $ip = null): Organizer
    {
        DB::transaction(function () use ($organizer, $actor, $data, $ip): void {
            $locked = Organizer::query()
                ->whereKey($organizer->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $oldSlug = $locked->slug;
            $locked->fill($data);

            $dirty = $locked->getDirty();
            $before = [];

            foreach (array_keys($dirty) as $field) {
                $before[$field] = $locked->getOriginal($field);
            }

            if (array_key_exists('slug', $dirty) && $dirty['slug'] !== $oldSlug) {
                // Kembali ke slug lama: hapus riwayat agar tidak terjadi loop.
                OrganizerSlugRedirect::query()
                    ->where('organizer_id', $locked->getKey())
                    ->where('slug', $dirty['slug'])
                    ->delete();

                // Redirect hanya diperlukan bila slug sudah tampil di halaman publik.
                $hasPublicEvents = $locked->events()
                    ->whereIn('status', [
                        Event::STATUS_PUBLISHED,
                        Event::STATUS_SOLD_OUT,
                        Event::STATUS_ENDED,
                    ])
                    ->exists();

                if ($hasPublicEvents && $oldSlug !== '') {
                    OrganizerSlugRedirect::query()->updateOrCreate(
                        ['slug' => $oldSlug],
                        ['organizer_id' => $locked->getKey()],
                    );
                }
            }

            $locked->save();

            $changes = [];

            foreach ($dirty as $field => $newValue) {
                $oldValue = $before[$field] ?? null;

                if ($field === 'bank_account_no') {
                    $oldValue = $this->maskAccount($oldValue);
                    $newValue = $this->maskAccount($newValue);
                }

                $changes[$field] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }

            if ($changes === []) {
                return;
            }

            $this->audit($actor, 'organizer.updated', $locked->getKey(), [
                'changes' => $changes,
            ], $ip);
        });

        return $organizer->refresh();
    }

    public function verify(
        Organizer $organizer,
        User $admin,
        bool $verified,
        ?string $note = null,
        ?string $ip = null,
    ): Organizer {
        DB::transaction(function () use ($organizer, $admin, $verified, $note, $ip): void {
            $locked = Organizer::query()
                ->whereKey($organizer->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $locked->forceFill([
                'is_verified' => $verified,
                'verified_at' => $verified ? now() : null,
                'verified_by' => $verified ? $admin->getKey() : null,
                'verification_note' => $note,
            ])->save();

            $this->audit(
                $admin,
                $verified ? 'organizer.verified' : 'organizer.unverified',
                $locked->getKey(),
                ['note' => $note],
                $ip,
            );
        });

        return $organizer->refresh();
    }

    private function slugExists(string $slug, ?string $ignoreOrganizerId = null): bool
    {
        $organizers = Organizer::query()->where('slug', $slug);

        if ($ignoreOrganizerId) {
            $organizers->whereKeyNot($ignoreOrganizerId);
        }

        if ($organizers->exists()) {
            return true;
        }

        return OrganizerSlugRedirect::query()
            ->where('slug', $slug)
            ->when(
                $ignoreOrganizerId,
                fn ($query) => $query->where('organizer_id', '!=', $ignoreOrganizerId),
            )
            ->exists();
    }

    private function maskAccount(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $value = (string) $value;

        return mb_strlen($value) <= 4 ? '****' : '****'.mb_substr($value, -4);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function audit(User $user, string $action, string $entityId, array $meta, ?string $ip): void
    {
        AuditLog::query()->create([
            'user_id' => $user->getKey(),
            'action' => $action,
            'entity' => 'organizer',
            'entity_id' => $entityId,
            'meta' => $meta,
            'ip_address' => $ip,
        ]);
    }
}
