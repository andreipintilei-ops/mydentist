<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class County extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'auto_code'];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function clinics(): HasMany
    {
        return $this->hasManyThrough(Clinic::class, City::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
