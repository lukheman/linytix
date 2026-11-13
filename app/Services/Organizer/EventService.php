<?php

namespace App\Services\Organizer;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use App\Notifications\EventApproved;
use App\Notifications\EventRejected;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EventService
{
    /**
     * Buat event draft baru beserta media yang diunggah.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $gallery
     * @param  array<int, array<string, mixed>>  $lineups
     */
    public function createDraft(
        Organizer $organizer,
        User $actor,
        array $data,
        ?UploadedFile $poster = null,
        ?UploadedFile $banner = null,
        array $gallery = [],
        array $lineups = [],
        ?string $ip = null,
    ): Event {
        $event = DB::transaction(function () use ($organizer, $data): Event {
            $timezone = $data['timezone'] ?? 'Asia/Jakarta';

            return Event::query()->create([
                'organizer_id' => $organizer->getKey(),
                'category_id' => $data['category_id'],
                'venue_id' => ($data['is_online'] ?? false) ? null : ($data['venue_id'] ?? null),
                'title' => $data['title'],
                'short_desc' => $data['short_desc'] ?? null,
                'slug' => $this->uniqueSlug($data['title']),
                'description' => $data['description'] ?? null,
                'terms' => $data['terms'] ?? null,
                'is_online' => (bool) ($data['is_online'] ?? false),
                'online_url' => ($data['is_online'] ?? false) ? ($data['online_url'] ?? null) : null,
                'timezone' => $timezone,
                'start_at' => $this->toUtc($data['start_at'] ?? null, $timezone),
                'doors_open_at' => $this->toUtc($data['doors_open_at'] ?? null, $timezone),
                'end_at' => $this->toUtc($data['end_at'] ?? null, $timezone),
                'min_price' => 0,
                'status' => Event::STATUS_DRAFT,
                'require_identity' => (bool) ($data['require_identity'] ?? false),
            ]);
        });

        $this->storeEventMedia($event, $poster, $banner, $gallery, $lineups);

        $this->audit($actor, 'event.created', $event, [
            'title' => $event->title,
            'status' => $event->status,
        ], $ip);

        return $event->refresh();
    }

    /**
     * Perbarui event sesuai statusnya. Event yang sudah tayang hanya boleh
     * diubah pada deskripsi dan galeri.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $gallery
     * @param  array<int, array<string, mixed>>|null  $lineups
     * @param  array<int, string>  $removeImages
     */
    public function update(
        Event $event,
        User $actor,
        array $data,
        ?UploadedFile $poster = null,
        ?UploadedFile $banner = null,
        array $gallery = [],
        ?array $lineups = null,
        array $removeImages = [],
        ?string $ip = null,
    ): Event {
        if (! $event->isFullyEditable() && ! $event->allowsNonCriticalEdits()) {
            throw ValidationException::withMessages([
                'status' => 'Event berstatus '.$event->statusLabel().' tidak dapat diubah saat ini.',
            ]);
        }

        $before = $event->only([
            'title',
            'category_id',
            'venue_id',
            'description',
            'poster_url',
            'banner_url',
            'is_online',
            'timezone',
            'start_at',
            'end_at',
        ]);

        DB::transaction(function () use ($event, $data): void {
            $payload = [];

            if ($event->isFullyEditable()) {
                $timezone = $data['timezone'] ?? $event->timezone;
                $isOnline = array_key_exists('is_online', $data)
                    ? (bool) $data['is_online']
                    : $event->is_online;

                $payload = [
                    'category_id' => $data['category_id'] ?? $event->category_id,
                    'venue_id' => $isOnline
                        ? null
                        : (array_key_exists('venue_id', $data) ? $data['venue_id'] : $event->venue_id),
                    'title' => $data['title'] ?? $event->title,
                    'short_desc' => array_key_exists('short_desc', $data) ? $data['short_desc'] : $event->short_desc,
                    'description' => array_key_exists('description', $data) ? $data['description'] : $event->description,
                    'terms' => array_key_exists('terms', $data) ? $data['terms'] : $event->terms,
                    'is_online' => $isOnline,
                    'online_url' => $isOnline
                        ? (array_key_exists('online_url', $data) ? $data['online_url'] : $event->online_url)
                        : null,
                    'timezone' => $timezone,
                    'start_at' => array_key_exists('start_at', $data)
                        ? $this->toUtc($data['start_at'], $timezone)
                        : $event->start_at,
                    'doors_open_at' => array_key_exists('doors_open_at', $data)
                        ? $this->toUtc($data['doors_open_at'], $timezone)
                        : $event->doors_open_at,
                    'end_at' => array_key_exists('end_at', $data)
                        ? $this->toUtc($data['end_at'], $timezone)
                        : $event->end_at,
                    'require_identity' => array_key_exists('require_identity', $data)
                        ? (bool) $data['require_identity']
                        : $event->require_identity,
                ];
            } elseif (array_key_exists('description', $data)) {
                $payload['description'] = $data['description'];
            }

            if ($payload !== []) {
                $event->fill($payload)->save();
            }
        });

        $this->storeEventMedia(
            $event,
            $event->isFullyEditable() ? $poster : null,
            $event->isFullyEditable() ? $banner : null,
            $gallery,
            $event->isFullyEditable() ? $lineups : null,
        );

        $this->removeImages($event, $removeImages);

        $changes = [];

        foreach ($before as $field => $oldValue) {
            $newValue = $event->getAttribute($field);

            if ($oldValue != $newValue) {
                $changes[$field] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }
        }

        $this->audit($actor, 'event.updated', $event, [
            'changes' => $changes,
            'gallery_added' => count($gallery),
            'gallery_removed' => count($removeImages),
        ], $ip);

        return $event->refresh();
    }

    public function delete(Event $event, User $actor, ?string $ip = null): void
    {
        if (! $event->canBeDeleted()) {
            throw ValidationException::withMessages([
                'status' => 'Hanya event draft atau ditolak yang belum pernah tayang dapat dihapus.',
            ]);
        }

        $eventId = $event->getKey();
        $title = $event->title;

        foreach ($event->images as $image) {
            $this->deleteMedia($image->image_url);
        }

        foreach ($event->lineups as $lineup) {
            $this->deleteMedia($lineup->photo_url);
        }

        $this->deleteMedia($event->poster_url);
        $this->deleteMedia($event->banner_url);

        $event->delete();

        $this->audit($actor, 'event.deleted', null, [
            'event_id' => $eventId,
            'title' => $title,
        ], $ip);
    }

    public function submitForReview(Event $event, User $actor, ?string $ip = null): Event
    {
        $errors = [];

        if (blank($event->title)) {
            $errors['title'] = 'Judul event wajib diisi.';
        }

        if (blank($event->category_id)) {
            $errors['category_id'] = 'Kategori event wajib dipilih.';
        }

        if (! $event->is_online && blank($event->venue_id)) {
            $errors['venue_id'] = 'Venue wajib dipilih untuk event offline.';
        }

        if ($event->is_online && blank($event->online_url)) {
            $errors['online_url'] = 'URL event online wajib diisi.';
        }

        if (blank($event->start_at)) {
            $errors['start_at'] = 'Waktu mulai wajib diisi.';
        }

        if (blank($event->end_at)) {
            $errors['end_at'] = 'Waktu selesai wajib diisi.';
        }

        if ($event->start_at && $event->end_at && $event->end_at->lte($event->start_at)) {
            $errors['end_at'] = 'Waktu selesai harus setelah waktu mulai.';
        }

        if (blank($event->timezone)) {
            $errors['timezone'] = 'Timezone wajib diisi.';
        }

        if (blank($event->description)) {
            $errors['description'] = 'Deskripsi event wajib diisi.';
        }

        if (blank($event->poster_url)) {
            $errors['poster'] = 'Poster event wajib diunggah.';
        }

        if (! $event->activeTicketTypes()->exists()) {
            $errors['tickets'] = 'Minimal satu jenis tiket aktif harus tersedia sebelum submit review.';
        }

        $capacityOverflow = $event->capacityOverflowFor($event->ticketQuotaTotal());

        if ($capacityOverflow) {
            $errors['tickets'] = 'Total kuota tiket ('.$capacityOverflow['total'].') melebihi kapasitas venue ('.$capacityOverflow['capacity'].'). Kurangi kuota sebelum submit review.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $event->forceFill([
            'status' => Event::STATUS_PENDING_REVIEW,
            'rejection_reason' => null,
        ])->save();

        $this->audit($actor, 'event.submitted', $event, [
            'status' => $event->status,
        ], $ip);

        return $event->refresh();
    }

    public function approve(Event $event, User $admin, ?string $ip = null): Event
    {
        $this->ensurePendingReview($event);

        $event->forceFill([
            'status' => Event::STATUS_PUBLISHED,
            'published_at' => now(),
            'approved_by' => $admin->getKey(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ])->save();

        $this->audit($admin, 'event.approved', $event, [
            'status' => $event->status,
        ], $ip);

        $event = $event->refresh();

        $this->notifyOrganizer($event, new EventApproved($event));

        return $event;
    }

    public function reject(Event $event, User $admin, string $reason, ?string $ip = null): Event
    {
        $this->ensurePendingReview($event);

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        $event->forceFill([
            'status' => Event::STATUS_REJECTED,
            'published_at' => null,
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => trim($reason),
        ])->save();

        $this->audit($admin, 'event.rejected', $event, [
            'status' => $event->status,
            'reason' => $event->rejection_reason,
        ], $ip);

        $event = $event->refresh();

        $this->notifyOrganizer($event, new EventRejected($event, (string) $event->rejection_reason));

        return $event;
    }

    public function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $counter = 2;

        while (Event::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<int, UploadedFile>  $gallery
     * @param  array<int, array<string, mixed>>|null  $lineups
     */
    private function storeEventMedia(
        Event $event,
        ?UploadedFile $poster,
        ?UploadedFile $banner,
        array $gallery,
        ?array $lineups,
    ): void {
        if ($poster) {
            $oldPoster = $event->poster_url;
            $event->forceFill(['poster_url' => $this->storeMedia($poster, 'events/'.$event->getKey().'/poster')])->save();
            $this->deleteMedia($oldPoster);
        }

        if ($banner) {
            $oldBanner = $event->banner_url;
            $event->forceFill(['banner_url' => $this->storeMedia($banner, 'events/'.$event->getKey().'/banner')])->save();
            $this->deleteMedia($oldBanner);
        }

        if ($gallery !== []) {
            $sortOrder = (int) ($event->images()->max('sort_order') ?? 0);

            foreach ($gallery as $index => $file) {
                $event->images()->create([
                    'image_url' => $this->storeMedia($file, 'events/'.$event->getKey().'/gallery'),
                    'sort_order' => $sortOrder + $index + 1,
                ]);
            }
        }

        if ($lineups !== null) {
            $this->syncLineups($event, $lineups);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncLineups(Event $event, array $rows): void
    {
        $kept = [];

        foreach (array_values($rows) as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $lineup = null;

            if (! empty($row['id'])) {
                $lineup = $event->lineups()->whereKey($row['id'])->first();
            }

            $payload = [
                'name' => $name,
                'perform_at' => $this->toUtc($row['perform_at'] ?? null, $event->timezoneLabel()),
                'sort_order' => $index,
            ];

            if (! empty($row['photo']) && $row['photo'] instanceof UploadedFile) {
                $payload['photo_url'] = $this->storeMedia($row['photo'], 'events/'.$event->getKey().'/lineup');

                if ($lineup?->photo_url) {
                    $this->deleteMedia($lineup->photo_url);
                }
            }

            if ($lineup) {
                $lineup->update($payload);
                $kept[] = $lineup->getKey();
            } else {
                $created = $event->lineups()->create($payload);
                $kept[] = $created->getKey();
            }
        }

        $removed = $event->lineups()
            ->when($kept !== [], fn ($query) => $query->whereNotIn('id', $kept))
            ->get();

        foreach ($removed as $lineup) {
            $this->deleteMedia($lineup->photo_url);
            $lineup->delete();
        }
    }

    /**
     * @param  array<int, string>  $ids
     */
    private function removeImages(Event $event, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $images = $event->images()->whereKey($ids)->get();

        foreach ($images as $image) {
            $this->deleteMedia($image->image_url);
            $image->delete();
        }
    }

    private function ensurePendingReview(Event $event): void
    {
        if ($event->status !== Event::STATUS_PENDING_REVIEW) {
            throw ValidationException::withMessages([
                'status' => 'Hanya event berstatus menunggu review yang dapat dimoderasi.',
            ]);
        }
    }

    private function storeMedia(UploadedFile $file, string $directory): string
    {
        $disk = config('ticketing.event_uploads.disk');

        $path = $file->store($directory, $disk);

        return Storage::disk($disk)->url($path);
    }

    private function deleteMedia(?string $url): void
    {
        if (blank($url)) {
            return;
        }

        $disk = config('ticketing.event_uploads.disk');
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return;
        }

        $path = ltrim($path, '/');
        $prefix = 'storage/';

        if (str_starts_with($path, $prefix)) {
            $path = substr($path, strlen($prefix));
        }

        if ($path !== '' && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    private function toUtc(?string $value, string $timezone): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value, $timezone)->utc();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function audit(?User $user, string $action, ?Event $event, array $meta, ?string $ip): void
    {
        AuditLog::query()->create([
            'user_id' => $user?->getKey(),
            'action' => $action,
            'entity' => 'event',
            'entity_id' => $event?->getKey() ?? ($meta['event_id'] ?? null),
            'meta' => $meta,
            'ip_address' => $ip,
        ]);
    }

    /**
     * Kirim email moderasi ke owner organizer (fallback ke kontak organizer).
     */
    private function notifyOrganizer(Event $event, BaseNotification $notification): void
    {
        $event->loadMissing('organizer.owner');

        $email = $event->organizer?->owner?->email
            ?? $event->organizer?->contact_email;

        if (blank($email)) {
            return;
        }

        Notification::route('mail', $email)->notify($notification);
    }
}
