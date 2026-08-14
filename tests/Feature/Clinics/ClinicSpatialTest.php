<?php

use App\Models\City;
use App\Models\Clinic;
use Database\Seeders\CitySeeder;
use Database\Seeders\CountySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([CountySeeder::class, CitySeeder::class]);

    $this->iasi = City::where('slug', 'iasi')->firstOrFail();
    $this->bucuresti = City::where('slug', 'bucuresti')->firstOrFail();

    // A handful of clinics in each city, scattered around the real centres.
    Clinic::factory()->count(8)->inCity($this->iasi)->create();
    Clinic::factory()->count(8)->inCity($this->bucuresti)->create();
});

// This is the canary for the axis-order footgun. If the migration or scopeNear
// swap latitude/longitude, every clinic lands off West Africa and a search
// centred on Iași returns nothing — so this test failing means the geo layer is
// broken, which is exactly what we want to catch.
it('returns Iași clinics and excludes București ones near the Iași centre', function () {
    $near = Clinic::near(47.1585, 27.6014, 10)->get();

    expect($near)->toHaveCount(8)
        ->and($near->pluck('city_id')->unique()->all())->toBe([$this->iasi->id]);
});

it('returns București clinics and excludes Iași ones near the București centre', function () {
    $near = Clinic::near(44.4268, 26.1025, 10)->get();

    expect($near)->toHaveCount(8)
        ->and($near->pluck('city_id')->unique()->all())->toBe([$this->bucuresti->id]);
});

it('orders results nearest-first with a distance in metres', function () {
    $near = Clinic::near(47.1585, 27.6014, 25)->get();

    $distances = $near->pluck('distance_m')->map(fn ($d) => (float) $d)->all();

    expect($distances)->toBe(collect($distances)->sort()->values()->all())
        ->and($distances)->each->toBeLessThanOrEqual(25_000);
});

it('excludes clinics outside the radius', function () {
    // 300 km from Iași still does not reach București (~325 km away).
    $near = Clinic::near(47.1585, 27.6014, 300)->get();

    expect($near->pluck('city_id')->unique()->all())->toBe([$this->iasi->id]);
});

it('hides demo clinics in production but shows real ones', function () {
    Clinic::factory()->demo()->inCity($this->iasi)->create();

    // Local/testing: demo rows are visible.
    expect(Clinic::where('is_demo', true)->count())->toBe(1);

    // Simulate production: the global scope must hide demo rows.
    app()->detectEnvironment(fn () => 'production');
    Clinic::clearBootedModels();

    expect(Clinic::where('is_demo', true)->count())->toBe(0)
        ->and(Clinic::withoutGlobalScope('excludeDemo')->where('is_demo', true)->count())->toBe(1);
});

it('only counts published reviews toward the rating aggregate', function () {
    $clinic = Clinic::factory()->inCity($this->iasi)->create();

    $clinic->reviews()->createMany([
        ['author_name' => 'A', 'rating' => 5, 'status' => 'published', 'published_at' => now()],
        ['author_name' => 'B', 'rating' => 3, 'status' => 'published', 'published_at' => now()],
        ['author_name' => 'C', 'rating' => 1, 'status' => 'pending'],
    ]);

    $clinic->recalculateRating();

    expect($clinic->rating_count)->toBe(2)
        ->and($clinic->rating_avg)->toBe(4.0);
});
