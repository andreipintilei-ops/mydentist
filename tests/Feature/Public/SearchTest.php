<?php

use App\Enums\ClinicStatus;
use App\Models\City;
use App\Models\Clinic;
use App\Models\OpeningHour;
use App\Models\Service;
use Database\Seeders\CitySeeder;
use Database\Seeders\CountySeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([CountySeeder::class, CitySeeder::class, ServiceSeeder::class]);
    $this->iasi = City::where('slug', 'iasi')->firstOrFail();
    $this->bucuresti = City::where('slug', 'bucuresti')->firstOrFail();
    $this->implant = Service::where('slug', 'implant-dentar')->firstOrFail();
});

it('serves the /cauta shell as noindex with the Search island', function () {
    $this->get(route('search'))
        ->assertOk()
        ->assertSee('noindex,follow', escape: false)
        ->assertSee('data-vue="Search"', escape: false);
});

it('returns only published clinics from the JSON feed', function () {
    Clinic::factory()->count(3)->inCity($this->iasi)->create();
    Clinic::factory()->inCity($this->iasi)->status(ClinicStatus::Pending)->create();

    $response = $this->getJson(route('search.clinics'));

    $response->assertOk()->assertJsonPath('count', 3);
});

it('filters the feed by a viewport bounding box', function () {
    Clinic::factory()->count(4)->inCity($this->iasi)->create();
    Clinic::factory()->count(5)->inCity($this->bucuresti)->create();

    // A tight box around Iași must exclude every București clinic.
    $response = $this->getJson(route('search.clinics', [
        'min_lat' => 47.05, 'max_lat' => 47.27,
        'min_lng' => 27.45, 'max_lng' => 27.75,
    ]));

    $response->assertOk()->assertJsonPath('count', 4);
});

it('filters by city, CNAS and minimum rating', function () {
    Clinic::factory()->inCity($this->iasi)->create(['cnas_contract' => true, 'rating_avg' => 4.6, 'rating_count' => 10]);
    Clinic::factory()->inCity($this->iasi)->create(['cnas_contract' => false, 'rating_avg' => 3.0, 'rating_count' => 2]);
    Clinic::factory()->inCity($this->bucuresti)->create(['cnas_contract' => true, 'rating_avg' => 5.0, 'rating_count' => 4]);

    $this->getJson(route('search.clinics', ['cnas' => 1]))->assertJsonPath('count', 2);
    $this->getJson(route('search.clinics', ['min_rating' => 4]))->assertJsonPath('count', 2);
    $this->getJson(route('search.clinics', ['city_id' => $this->iasi->id]))->assertJsonPath('count', 2);
    $this->getJson(route('search.clinics', ['city_id' => $this->iasi->id, 'cnas' => 1, 'min_rating' => 4]))
        ->assertJsonPath('count', 1);
});

it('filters by service offered', function () {
    $with = Clinic::factory()->inCity($this->iasi)->create();
    $with->services()->attach($this->implant);
    Clinic::factory()->inCity($this->iasi)->create(); // no services

    $this->getJson(route('search.clinics', ['service_id' => $this->implant->id]))
        ->assertOk()->assertJsonPath('count', 1);
});

it('filters "open now" against the clinic\'s hours in Romanian time', function () {
    $now = Carbon::now('Europe/Bucharest');
    $today = ($now->dayOfWeek + 6) % 7;

    $open = Clinic::factory()->inCity($this->iasi)->create();
    OpeningHour::create([
        'clinic_id' => $open->id, 'day_of_week' => $today,
        'opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false,
    ]);

    $closed = Clinic::factory()->inCity($this->iasi)->create();
    OpeningHour::create([
        'clinic_id' => $closed->id, 'day_of_week' => $today,
        'opens_at' => null, 'closes_at' => null, 'is_closed' => true,
    ]);

    $response = $this->getJson(route('search.clinics', ['open_now' => 1]));

    $response->assertOk()->assertJsonPath('count', 1)
        ->assertJsonPath('clinics.0.id', $open->id);
});

it('reports whether the result set hit the cap', function () {
    Clinic::factory()->count(3)->inCity($this->iasi)->create();

    // Well under the ceiling, so the count is a real total and the UI shows it
    // as-is rather than as "300+".
    $this->getJson(route('search.clinics'))
        ->assertOk()
        ->assertJsonPath('count', 3)
        ->assertJsonPath('capped', false);
});

it('returns the compact clinic shape the map/list expect', function () {
    $clinic = Clinic::factory()->inCity($this->iasi)->create(['cnas_contract' => true]);
    $clinic->services()->attach($this->implant);

    $this->getJson(route('search.clinics'))
        ->assertOk()
        ->assertJsonStructure([
            'count',
            'clinics' => [['id', 'name', 'url', 'lat', 'lng', 'address', 'city', 'cnas', 'rating_avg', 'rating_count', 'services']],
        ]);
});
