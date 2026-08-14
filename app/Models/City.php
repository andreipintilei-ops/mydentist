<?php

namespace App\Models;

use App\Concerns\RecordsSlugHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $county_id
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property float $lat
 * @property float $lng
 * @property int|null $population
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $full_name
 * @property-read ?County $county
 * @property-read Collection<int, Clinic> $clinics
 */
class City extends Model
{
    /** @use HasFactory<Factory<City>> */
    use HasFactory, RecordsSlugHistory;

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

    /**
     * @return BelongsTo<County, $this>
     */
    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    /**
     * @return HasMany<Clinic, $this>
     */
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
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
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
