<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventStaffInvitation extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'event_id',
        'email',
        'gate',
        'token_hash',
        'invited_by',
        'accepted_by',
        'expires_at',
        'accepted_at',
        'revoked_at',
        'last_sent_at',
        'send_count',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'send_count' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return ! $this->isAccepted() && ! $this->isRevoked() && ! $this->isExpired();
    }

    public function status(): string
    {
        return match (true) {
            $this->isAccepted() => self::STATUS_ACCEPTED,
            $this->isRevoked() => self::STATUS_REVOKED,
            $this->isExpired() => self::STATUS_EXPIRED,
            default => self::STATUS_PENDING,
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            self::STATUS_ACCEPTED => 'Diterima',
            self::STATUS_REVOKED => 'Dibatalkan',
            self::STATUS_EXPIRED => 'Kedaluwarsa',
            default => 'Menunggu',
        };
    }

    public function scopePending(Builder $query): Builder
    {
        return $query
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

    public static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    public static function findByToken(string $rawToken): ?self
    {
        if ($rawToken === '') {
            return null;
        }

        return static::query()
            ->where('token_hash', static::hashToken($rawToken))
            ->first();
    }
}
