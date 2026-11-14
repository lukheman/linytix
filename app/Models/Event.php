<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_SOLD_OUT = 'sold_out';

    public const STATUS_ENDED = 'ended';

    public const STATUS_CANCELLED = 'cancelled';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organizer_id',
        'category_id',
        'venue_id',
        'title',
        'short_desc',
        'slug',
        'description',
        'terms',
        'poster_url',
        'banner_url',
        'is_online',
        'online_url',
        'timezone',
        'start_at',
        'doors_open_at',
        'end_at',
        'min_price',
        'status',
        'is_featured',
        'require_identity',
        'view_count',
        'published_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'doors_open_at' => 'datetime',
            'end_at' => 'datetime',
            'published_at' => 'datetime',
            'approved_at' => 'datetime',
            'is_online' => 'boolean',
            'is_featured' => 'boolean',
            'require_identity' => 'boolean',
            'view_count' => 'integer',
            'min_price' => 'integer',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(EventImage::class)->orderBy('sort_order');
    }

    public function lineups(): HasMany
    {
        return $this->hasMany(EventLineup::class)->orderBy('sort_order');
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function activeTicketTypes(): HasMany
    {
        return $this->ticketTypes()->where('is_active', true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_staff')->withPivot(['user_id', 'gate']);
    }

    public function staffInvitations(): HasMany
    {
        return $this->hasMany(EventStaffInvitation::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PUBLISHED, self::STATUS_SOLD_OUT]);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->published()->where('start_at', '>=', now());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_at', '>=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        $keyword = trim((string) $keyword);

        if (mb_strlen($keyword) < 2) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($keyword): void {
            $inner->where('title', 'like', '%'.$keyword.'%')
                ->orWhere('short_desc', 'like', '%'.$keyword.'%');
        });
    }

    public function isOnSale(): bool
    {
        return in_array($this->status, [self::STATUS_PUBLISHED, self::STATUS_SOLD_OUT], true);
    }

    /**
     * Event masih dapat diubah penuh oleh organizer (termasuk field penjualan).
     */
    public function isFullyEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true)
            && $this->published_at === null;
    }

    /**
     * Kuota dan jadwal jual tiket masih boleh diubah kapan pun selama event
     * tidak dibatalkan, termasuk setelah event tayang.
     */
    public function canAdjustSalesSettings(): bool
    {
        return $this->status !== self::STATUS_CANCELLED;
    }

    /**
     * Event yang sudah tayang hanya boleh diubah pada konten non-penjualan.
     */
    public function allowsNonCriticalEdits(): bool
    {
        return in_array($this->status, [
            self::STATUS_PUBLISHED,
            self::STATUS_SOLD_OUT,
            self::STATUS_ENDED,
        ], true);
    }

    public function canBeDeleted(): bool
    {
        return $this->isFullyEditable() && $this->published_at === null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_REVIEW => 'Menunggu Review',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_SOLD_OUT => 'Sold Out',
            self::STATUS_ENDED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => str($this->status)->headline()->toString(),
        };
    }

    public function statusVariant(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_PENDING_REVIEW => 'warning',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_PUBLISHED => 'success',
            self::STATUS_SOLD_OUT => 'info',
            self::STATUS_ENDED => 'secondary',
            self::STATUS_CANCELLED => 'dark',
            default => 'secondary',
        };
    }

    public function isSoldOut(): bool
    {
        $types = $this->relationLoaded('ticketTypes')
            ? $this->ticketTypes->where('is_active', true)
            : $this->activeTicketTypes()->get();

        if ($types->isEmpty()) {
            return false;
        }

        return $types->every(fn (TicketType $type) => $type->remainingQuota() <= 0);
    }

    public function remainingQuota(): int
    {
        $types = $this->relationLoaded('ticketTypes')
            ? $this->ticketTypes->where('is_active', true)
            : $this->activeTicketTypes()->get();

        return (int) $types->sum(fn (TicketType $type) => $type->remainingQuota());
    }

    /**
     * Total kuota seluruh jenis tiket, termasuk yang nonaktif.
     * Nilai ini dibandingkan dengan kapasitas venue saat submit review.
     */
    public function ticketQuotaTotal(?string $excludeTicketTypeId = null): int
    {
        return (int) $this->ticketTypes()
            ->when($excludeTicketTypeId, fn ($query) => $query->whereKeyNot($excludeTicketTypeId))
            ->sum('quota');
    }

    /**
     * Kapasitas venue untuk event offline; event online tidak dibatasi venue.
     */
    public function venueCapacity(): ?int
    {
        if ($this->is_online) {
            return null;
        }

        return $this->venue?->capacity;
    }

    /**
     * @return array{total: int, capacity: int, excess: int}|null
     */
    public function capacityOverflowFor(int $projectedQuotaTotal): ?array
    {
        $capacity = $this->venueCapacity();

        if ($capacity === null || $projectedQuotaTotal <= $capacity) {
            return null;
        }

        return [
            'total' => $projectedQuotaTotal,
            'capacity' => $capacity,
            'excess' => $projectedQuotaTotal - $capacity,
        ];
    }

    public function exceedsVenueCapacity(): bool
    {
        $capacity = $this->venueCapacity();

        if ($capacity === null) {
            return false;
        }

        $total = $this->ticket_quota_total ?? $this->ticketQuotaTotal();

        return (int) $total > $capacity;
    }

    public function lowestActivePrice(): int
    {
        $types = $this->relationLoaded('ticketTypes')
            ? $this->ticketTypes->where('is_active', true)
            : $this->activeTicketTypes()->get();

        return (int) ($types->min('price') ?? $this->min_price ?? 0);
    }

    public function posterUrl(): ?string
    {
        return $this->poster_url ?: null;
    }

    public function displayPosterUrl(): string
    {
        return $this->poster_url
            ?: asset('assets/img/event-placeholder.svg');
    }

    public function displayBannerUrl(): string
    {
        return $this->banner_url
            ?: $this->poster_url
            ?: asset('assets/img/event-placeholder.svg');
    }

    public function timezoneLabel(): string
    {
        return $this->timezone ?: 'Asia/Jakarta';
    }

    public function startsAtLocal(): ?CarbonInterface
    {
        return $this->start_at?->copy()->setTimezone($this->timezoneLabel());
    }

    public function endsAtLocal(): ?CarbonInterface
    {
        return $this->end_at?->copy()->setTimezone($this->timezoneLabel());
    }
}
