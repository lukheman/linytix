<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'address',
        'city',
        'province',
        'capacity',
        'latitude',
        'longitude',
        'maps_url',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function coordinates(): ?string
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return $this->latitude.','.$this->longitude;
    }
}
