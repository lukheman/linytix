<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'order_number',
        'idempotency_key',
        'user_id',
        'event_id',
        'voucher_id',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'holder_names',
        'subtotal',
        'discount_amount',
        'admin_fee',
        'payment_fee',
        'tax_amount',
        'total_amount',
        'status',
        'expires_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'holder_names' => 'array',
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'admin_fee' => 'integer',
            'payment_fee' => 'integer',
            'tax_amount' => 'integer',
            'total_amount' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Order masih bisa dibayar selama pending dan belum melewati batas waktu.
     */
    public function canBePaid(): bool
    {
        return $this->isPending() && ! $this->isExpired();
    }
}
