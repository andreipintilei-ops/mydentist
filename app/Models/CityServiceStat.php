<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
