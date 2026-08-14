<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $clinic_id
 * @property int $day_of_week
 * @property string|null $opens_at
 * @property string|null $closes_at
 * @property bool $is_closed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $day_name
 * @property-read ?Clinic $clinic
 */
class OpeningHour extends Model
{
    /** @use HasFactory<Factory<OpeningHour>> */
    use HasFactory;

    protected $fillable = [
        'clinic_id', 'day_of_week', 'opens_at', 'closes_at', 'is_closed',
    ];

    /**
     * 0 = Monday ... 6 = Sunday.
     */
    public const DAYS_RO = [
        0 => 'Luni', 1 => 'Marți', 2 => 'Miercuri', 3 => 'Joi',
        4 => 'Vineri', 5 => 'Sâmbătă', 6 => 'Duminică',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_closed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Clinic, $this>
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function getDayNameAttribute(): string
    {
        return self::DAYS_RO[$this->day_of_week] ?? '';
    }
}
