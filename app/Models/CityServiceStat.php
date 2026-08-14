<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $city_id
 * @property int $service_id
 * @property int $clinic_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ?City $city
 * @property-read ?Service $service
 */
class CityServiceStat extends Model
{
    protected $table = 'city_service_stats';

    // Composite primary key, no auto-increment id.
    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = ['city_id', 'service_id', 'clinic_count'];

    protected function casts(): array
    {
        return ['clinic_count' => 'integer'];
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
