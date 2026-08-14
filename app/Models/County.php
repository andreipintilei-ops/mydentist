<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $auto_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, City> $cities
 * @property-read Collection<int, Clinic> $clinics
 */
class County extends Model
{
    /** @use HasFactory<Factory<County>> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'auto_code'];

    /**
     * @return HasMany<City, $this>
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * @return HasManyThrough<Clinic, City, $this>
     */
    public function clinics(): HasManyThrough
    {
        return $this->hasManyThrough(Clinic::class, City::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
