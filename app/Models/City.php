<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'county_id', 'name', 'slug', 'type', 'lat', 'lng', 'population',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'population' => 'integer',
        ];
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function clinics(): HasMany
    {
        return $this->hasMany(Clinic::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Cities big enough to be worth their own landing page. Communes are
     * excluded by default — a commune page with one clinic is thin content
     * and dilutes crawl budget.
     */
    public function scopeNotable(Builder $query): Builder
    {
        return $query->whereIn('type', ['municipiu', 'oras']);
    }

    /**
     * "Iași, județul Iași" — used in listings where the county disambiguates
     * towns that share a name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->name === $this->county?->name
            ? $this->name
            : "{$this->name}, județul {$this->county?->name}";
    }
}
