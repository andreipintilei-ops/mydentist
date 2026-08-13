<?php

use App\Models\City;
use App\Models\County;
use App\Models\Service;
use Database\Seeders\CitySeeder;
use Database\Seeders\CountySeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([CountySeeder::class, CitySeeder::class, ServiceSeeder::class]);
});

it('seeds all 41 counties plus București', function () {
    expect(County::count())->toBe(42)
        ->and(County::where('auto_code', 'B')->value('name'))->toBe('București')
        ->and(County::where('auto_code', 'IS')->value('name'))->toBe('Iași');
});

it('strips Romanian diacritics from slugs', function () {
    expect(County::where('auto_code', 'IS')->value('slug'))->toBe('iasi')
        ->and(County::where('auto_code', 'BN')->value('slug'))->toBe('bistrita-nasaud')
        ->and(City::where('name', 'Târgu Mureș')->value('slug'))->toBe('targu-mures')
        ->and(City::where('name', 'Râmnicu Vâlcea')->value('slug'))->toBe('ramnicu-valcea');
});

it('never issues the same city slug twice', function () {
    // Two towns sharing a slug would mean two landing pages competing for one
    // URL, so this is a hard invariant rather than a nicety.
    $duplicates = City::query()
        ->selectRaw('slug, count(*) as total')
        ->groupBy('slug')
        ->havingRaw('total > 1')
        ->pluck('slug');

    expect($duplicates)->toBeEmpty();
});

it('matches city names ignoring diacritics and case', function () {
    // This is what utf8mb4_0900_ai_ci buys us: a Romanian user typing "iasi"
    // on a keyboard without diacritics still finds Iași. If someone downgrades
    // the collation in config/database.php, this test is the tripwire.
    expect(City::where('name', 'iasi')->value('name'))->toBe('Iași')
        ->and(City::where('name', 'IAȘI')->value('name'))->toBe('Iași')
        ->and(City::where('name', 'bucuresti')->value('name'))->toBe('București')
        ->and(City::where('name', 'targu mures')->value('name'))->toBe('Târgu Mureș');
});

it('matches partial city names ignoring diacritics', function () {
    // The same fold has to survive LIKE, which is what the search box uses.
    expect(City::where('name', 'like', '%ramnicu%')->value('name'))->toBe('Râmnicu Vâlcea');
});

it('links every city to a real county', function () {
    expect(City::whereDoesntHave('county')->count())->toBe(0);
});

it('places Iași and București at their real coordinates', function () {
    $iasi = City::where('slug', 'iasi')->first();
    $bucuresti = City::where('slug', 'bucuresti')->first();

    // Sanity-check the lat/lng ordering at the data layer: latitude ~47 and
    // longitude ~27 for Iași. Swapped values would put it in the Indian Ocean.
    expect($iasi->lat)->toBeGreaterThan(47.0)->toBeLessThan(47.3)
        ->and($iasi->lng)->toBeGreaterThan(27.4)->toBeLessThan(27.8)
        ->and($bucuresti->lat)->toBeGreaterThan(44.3)->toBeLessThan(44.6)
        ->and($bucuresti->lng)->toBeGreaterThan(25.9)->toBeLessThan(26.3);
});

it('seeds the dental specialties with descriptions', function () {
    expect(Service::count())->toBe(8)
        ->and(Service::where('slug', 'implant-dentar')->value('name'))->toBe('Implant dentar')
        ->and(Service::whereNull('description')->count())->toBe(0);
});

it('is idempotent', function () {
    $before = [County::count(), City::count(), Service::count()];

    $this->seed([CountySeeder::class, CitySeeder::class, ServiceSeeder::class]);

    expect([County::count(), City::count(), Service::count()])->toBe($before);
});
