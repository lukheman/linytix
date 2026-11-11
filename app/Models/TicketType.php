<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketType extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price',
        'quota',
        'sold_count',
        'min_per_order',
        'max_per_order',
        'sale_start_at',
        'sale_end_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quota' => 'integer',
            'sold_count' => 'integer',
            'min_per_order' => 'integer',
            'max_per_order' => 'integer',
            'sale_start_at' => 'datetime',
            'sale_end_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function remainingQuota(): int
    {
        return max(0, $this->quota - $this->sold_count);
    }

    public function isOnSaleNow(): bool
    {
        if (! $this->is_active || $this->remainingQuota() <= 0) {
            return false;
        }

        $now = now();

        if ($this->sale_start_at && $now->lt($this->sale_start_at)) {
            return false;
        }

        if ($this->sale_end_at && $now->gt($this->sale_end_at)) {
            return false;
        }

        return true;
    }

    public function saleStatus(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        if ($this->remainingQuota() <= 0) {
            return 'sold_out';
        }

        if ($this->sale_start_at && now()->lt($this->sale_start_at)) {
            return 'not_started';
        }

        if ($this->sale_end_at && now()->gt($this->sale_end_at)) {
            return 'closed';
        }

        return 'open';
    }

    public function saleStatusLabel(): string
    {
        return match ($this->saleStatus()) {
            'inactive' => 'Tidak dijual',
            'sold_out' => 'Habis',
            'not_started' => 'Belum dijual',
            'closed' => 'Ditutup',
            default => 'Tersedia',
        };
    }
}
