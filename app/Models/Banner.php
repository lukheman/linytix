<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'image_url',
        'link_url',
        'sort_order',
        'start_at',
        'end_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scopeActiveNow($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($inner) => $inner->whereNull('start_at')->orWhere('start_at', '<=', now()))
            ->where(fn ($inner) => $inner->whereNull('end_at')->orWhere('end_at', '>=', now()));
    }
}
