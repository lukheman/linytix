<?php

namespace App\Models;

use App\Models\Concerns\HasRoles;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'google_id',
        'password',
        'avatar',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Organizer yang dimiliki pengguna.
     */
    public function organizers(): HasMany
    {
        return $this->hasMany(Organizer::class, 'owner_user_id');
    }

    /**
     * Event tempat pengguna ditugaskan sebagai staff.
     */
    public function eventAssignments(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_staff')->withPivot(['user_id', 'gate']);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Kirim email verifikasi melalui queue.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * Hubungkan order guest dengan email yang sama ke akun ini.
     *
     * Hanya order tanpa pemilik yang diklaim agar order milik user lain tidak
     * ikut berpindah.
     */
    public function claimGuestOrders(): int
    {
        return Order::query()
            ->whereNull('user_id')
            ->whereRaw('LOWER(buyer_email) = ?', [mb_strtolower($this->email)])
            ->update(['user_id' => $this->id]);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the user's avatar URL
     */
    public function avatarUrl(): ?string
    {
        if ($this->avatar) {
            return Storage::disk('public')->url($this->avatar);
        }

        return null;
    }

    /**
     * Check if user has an avatar
     */
    public function hasAvatar(): bool
    {
        return ! empty($this->avatar);
    }
}
