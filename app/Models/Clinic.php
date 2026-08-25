<?php

namespace App\Models;

use App\Concerns\RecordsSlugHistory;
use App\Enums\ClinicStatus;
use Database\Factories\ClinicFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $city_id
 * @property string $address
 * @property float $lat
 * @property float $lng
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $description
 * @property ClinicStatus $status
 * @property bool $cnas_contract
 * @property bool $is_demo
 * @property int|null $claimed_by
 * @property Carbon|null $verified_at
 * @property float $rating_avg
 * @property int $rating_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ?string $directions_url
 * @property-read ?City $city
 * @property-read Collection<int, Service> $services
 * @property-read Collection<int, OpeningHour> $openingHours
 * @property-read Collection<int, ClinicPhoto> $photos
 * @property-read Collection<int, Review> $reviews
 * @property-read Collection<int, Claim> $claims
 * @property-read ?User $owner
 */
class Clinic extends Model
{
    /** @use HasFactory<ClinicFactory> */
    use HasFactory, RecordsSlugHistory;

    protected $fillable = [
        'name', 'slug', 'city_id', 'address', 'lat', 'lng',
        'phone', 'email', 'website', 'description', 'status',
        'cnas_contract', 'is_demo', 'claimed_by', 'verified_at',
        'rating_avg', 'rating_count',
    ];

    /**
     * `location` is a binary spatial column maintained by the database (a STORED
     * generated column on MySQL 8, sync triggers on MariaDB). It is never written
     * from PHP and never serialized — it is deliberately absent from $fillable and
     * hidden here so it does not leak WKB bytes into JSON/Inertia payloads.
     */
    protected $hidden = ['location'];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'status' => ClinicStatus::class,
            'cnas_contract' => 'boolean',
            'is_demo' => 'boolean',
            'verified_at' => 'datetime',
            'rating_avg' => 'float',
            'rating_count' => 'integer',
        ];
    }

    /**
     * Fictional seed clinics must never reach production search results or
     * sitemaps — indexing invented practices would mislead patients and breach
     * Google's policies for medical/local content. In production every query is
     * silently scoped to real clinics; locally the demo data is visible so the
     * site can be built and reviewed. `clinics:purge-demo` removes them entirely
     * before launch.
     */
    protected static function booted(): void
    {
        if (app()->environment('production')) {
            static::addGlobalScope('excludeDemo', function (Builder $query): void {
                $query->where($query->getModel()->getTable().'.is_demo', false);
            });
        }
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ---- Relations -------------------------------------------------------

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    /**
     * @return HasMany<OpeningHour, $this>
     */
    public function openingHours(): HasMany
    {
        return $this->hasMany(OpeningHour::class)->orderBy('day_of_week');
    }

    /**
     * @return HasMany<ClinicPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ClinicPhoto::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<Claim, $this>
     */
    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    /**
     * The registered user who successfully claimed this listing (Phase 7).
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    // ---- Scopes ----------------------------------------------------------

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where($this->getTable().'.status', ClinicStatus::Published->value);
    }

    /**
     * Accepts a City model or a city id.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeInCity(Builder $query, City|int $city): Builder
    {
        return $query->where('city_id', $city instanceof City ? $city->id : $city);
    }

    /**
     * Clinics within $radiusKm of a point, nearest first, with a `distance_m`
     * column added to each row.
     *
     * Two-stage to actually use the SPATIAL INDEX: ST_Distance_Sphere alone
     * cannot, so a PHP-computed bounding box prefilters via MBRContains (index-
     * backed), then ST_Distance_Sphere refines and sorts the survivors.
     *
     * AXIS ORDER is the whole game — this method is the ONLY place (besides the
     * clinics migration) that knows about it. Both engines want POINT(lng, lat):
     * MariaDB because it is planar, MySQL because POINT() builds a planar (x, y)
     * pair and a geographic SRS reads x as longitude. ST_GeomFromText(wkt, 4326)
     * is the exception — it honours EPSG axis order, so its WKT is "lat lng",
     * which is why the polygon below is built (lat, lng) on MySQL only.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeNear(Builder $query, float $lat, float $lng, float $radiusKm = 10): Builder
    {
        $radiusM = $radiusKm * 1000;

        // Bounding box. ~111.32 km per degree of latitude everywhere; longitude
        // degrees shrink toward the poles by cos(lat). Pad generously — the box
        // only prefilters, ST_Distance_Sphere does the exact cut.
        $latDelta = $radiusKm / 111.32;
        $lngDelta = $radiusKm / (111.32 * max(cos(deg2rad($lat)), 0.01));
        $minLat = $lat - $latDelta;
        $maxLat = $lat + $latDelta;
        $minLng = $lng - $lngDelta;
        $maxLng = $lng + $lngDelta;

        $table = $this->getTable();

        if (DB::connection($this->getConnectionName())->getDriverName() === 'mysql') {
            // WKT polygon is (lat, lng) — ST_GeomFromText honours EPSG axis order.
            // POINT() does not, so it takes (lng, lat). Both tagged SRID 4326.
            $polygon = $this->boxPolygon($minLat, $minLng, $maxLat, $maxLng);

            return $query
                ->select("{$table}.*")
                ->selectRaw(
                    'ST_Distance_Sphere(location, ST_SRID(POINT(?, ?), 4326)) AS distance_m',
                    [$lng, $lat]
                )
                ->whereRaw('MBRContains(ST_GeomFromText(?, 4326), location)', [$polygon])
                ->whereRaw(
                    'ST_Distance_Sphere(location, ST_SRID(POINT(?, ?), 4326)) <= ?',
                    [$lng, $lat, $radiusM]
                )
                ->orderBy('distance_m');
        }

        // MariaDB: (lng, lat) order, no SRID.
        $polygon = $this->boxPolygon($minLng, $minLat, $maxLng, $maxLat);

        return $query
            ->select("{$table}.*")
            ->selectRaw('ST_Distance_Sphere(location, POINT(?, ?)) AS distance_m', [$lng, $lat])
            ->whereRaw('MBRContains(ST_GeomFromText(?), location)', [$polygon])
            ->whereRaw('ST_Distance_Sphere(location, POINT(?, ?)) <= ?', [$lng, $lat, $radiusM])
            ->orderBy('distance_m');
    }

    /**
     * Clinics whose location falls inside a lat/lng bounding box — the map's
     * current viewport. Index-backed via MBRContains. Same axis-order rules as
     * scopeNear(): MySQL 8 stores (lat, lng), MariaDB stores (lng, lat).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeInBounds(Builder $query, float $minLat, float $minLng, float $maxLat, float $maxLng): Builder
    {
        if (DB::connection($this->getConnectionName())->getDriverName() === 'mysql') {
            $polygon = $this->boxPolygon($minLat, $minLng, $maxLat, $maxLng);

            return $query->whereRaw('MBRContains(ST_GeomFromText(?, 4326), location)', [$polygon]);
        }

        $polygon = $this->boxPolygon($minLng, $minLat, $maxLng, $maxLat);

        return $query->whereRaw('MBRContains(ST_GeomFromText(?), location)', [$polygon]);
    }

    /**
     * A closed rectangle in WKT. Coordinate order is the caller's responsibility
     * (it must match the axis order the stored points use). %F is locale-agnostic
     * so the RO locale's decimal comma never corrupts the WKT.
     */
    private function boxPolygon(float $minX, float $minY, float $maxX, float $maxY): string
    {
        return sprintf(
            'POLYGON((%1$F %2$F, %3$F %2$F, %3$F %4$F, %1$F %4$F, %1$F %2$F))',
            $minX, $minY, $maxX, $maxY
        );
    }

    // ---- Behaviour -------------------------------------------------------

    /**
     * A globally-unique slug derived from a name, e.g. "Dental Art" -> "dental-art",
     * with a short random suffix only when the base is already taken. The base
     * slug is diacritic-free ASCII (Str::slug), matching the taxonomy's URLs.
     */
    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'clinica';
        $slug = $base;

        while (static::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(5));
        }

        return $slug;
    }

    /**
     * A Google Maps "directions to here" link, or null until the clinic is
     * geocoded. Uses coordinates (not the address) so it always lands on the
     * exact spot the map pin shows.
     */
    public function getDirectionsUrlAttribute(): ?string
    {
        if (! $this->lat || ! $this->lng) {
            return null;
        }

        return 'https://www.google.com/maps/dir/?api=1&destination='.$this->lat.','.$this->lng;
    }

    /**
     * Recompute the denormalised rating aggregates from published reviews.
     * Called after a review is approved/hidden so listing pages never AVG() live.
     */
    public function recalculateRating(): void
    {
        $count = $this->reviews()->where('status', 'published')->count();
        $avg = (float) $this->reviews()->where('status', 'published')->avg('rating');

        $this->forceFill([
            'rating_count' => $count,
            'rating_avg' => round($avg, 2),
        ])->save();
    }
}
