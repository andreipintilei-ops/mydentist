<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Clinic> $clinics
 */
class Service extends Model
{
    /** @use HasFactory<Factory<Service>> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'sort_order'];

    /**
     * @return BelongsToMany<Clinic, $this>
     */
    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
