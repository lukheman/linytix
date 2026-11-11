<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organizer extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'owner_user_id',
        'name',
        'slug',
        'description',
        'logo_url',
        'contact_email',
        'contact_phone',
        'bank_name',
        'bank_account_no',
        'bank_account_name',
        'is_verified',
        'verified_at',
        'verified_by',
        'verification_note',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function slugRedirects(): HasMany
    {
        return $this->hasMany(OrganizerSlugRedirect::class);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_url ?: null;
    }

    public function maskedBankAccountNo(): ?string
    {
        if (! $this->bank_account_no) {
            return null;
        }

        $number = (string) $this->bank_account_no;

        return mb_strlen($number) <= 4 ? '****' : '****'.mb_substr($number, -4);
    }
}
