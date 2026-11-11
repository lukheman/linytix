<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    public const CUSTOMER = 'customer';

    public const ORGANIZER = 'organizer';

    public const STAFF = 'staff';

    public const ADMIN = 'admin';

    protected $fillable = ['name', 'label', 'description'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
