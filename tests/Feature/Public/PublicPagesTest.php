<?php

use App\Enums\ClinicStatus;
use App\Models\City;
use App\Models\Clinic;
use App\Models\Service;
use App\Support\Seo\SeoMeta;
use Database\Seeders\CitySeeder;
use Database\Seeders\CountySeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([CountySeeder::class, CitySeeder::class, ServiceSeeder::class]);

    $this->iasi = City::where('slug', 'iasi')->firstOrFail();
    $this->implant = Service::where('slug', 'implant-dentar')->firstOrFail();
});

/**
 * Create $n published clinics in Iași offering the implant service, then rebuild
 * the stats table the thin-content guard reads.
 *
 * @return Collection<int, Clinic>
 */
function seedImplantClinics(City $city, Service $service, int $n): Collection
{
    $clinics = Clinic::factory()->count($n)->inCity($city)->create();
    $clinics->each(fn (Clinic $c) => $c->services()->attach($service));

    test()->artisan('stats:rebuild-city-service');

    return $clinics;
}

it('renders the home page', function () {
    $this->get(route('home'))->assertOk();
});

// The definitive SEO check: clinic names must appear in the RAW body with no JS.
it('server-renders the city page with clinic names, a self-canonical and ItemList JSON-LD', function () {
    $clinics = seedImplantClinics($this->iasi, $this->implant, 3);
    $named = $clinics->first();

    $response = $this->get(route('city.show', $this->iasi));

    $response->assertOk()
        ->assertSee($named->name, escape: false)
        ->assertSee('clinic-card', escape: false)
        ->assertSee('<link rel="canonical" href="'.route('city.show', $this->iasi).'">', escape: false)
        ->assertSee('"@type": "ItemList"', escape: false)
        ->assertSee('"@type": "BreadcrumbList"', escape: false);
});

it('renders a city × service page when at or above the threshold', function () {
    seedImplantClinics($this->iasi, $this->implant, 3);

    $this->get(route('city.service', [$this->iasi, $this->implant]))
        ->assertOk()
        ->assertSee('Implant dentar în Iași', escape: false);
});

it('301s a thin city × service page to the city page', function () {
    seedImplantClinics($this->iasi, $this->implant, 2); // below the threshold of 3

    $this->get(route('city.service', [$this->iasi, $this->implant]))
        ->assertRedirect(route('city.show', $this->iasi))
        ->assertStatus(301);
});

it('renders a published clinic profile with Dentist JSON-LD', function () {
    $clinic = Clinic::factory()->inCity($this->iasi)->create();

    $this->get(route('clinic.show', $clinic))
        ->assertOk()
        ->assertSee($clinic->name, escape: false)
        ->assertSee('"@type": "Dentist"', escape: false)
        ->assertSee('"@type": "BreadcrumbList"', escape: false);
});

it('emits aggregateRating only when the clinic has published reviews', function () {
    $withReviews = Clinic::factory()->inCity($this->iasi)->create();
    $withReviews->reviews()->create([
        'author_name' => 'Ana', 'rating' => 5, 'status' => 'published', 'published_at' => now(),
    ]);
    $withReviews->recalculateRating();

    $noReviews = Clinic::factory()->inCity($this->iasi)->create();

    $this->get(route('clinic.show', $withReviews))->assertSee('"@type": "AggregateRating"', escape: false);
    $this->get(route('clinic.show', $noReviews))->assertDontSee('AggregateRating');
});

it('404s an unpublished clinic even though the slug resolves', function () {
    $draft = Clinic::factory()->inCity($this->iasi)->status(ClinicStatus::Pending)->create();

    $this->get(route('clinic.show', $draft))->assertNotFound();
});

it('301s a renamed clinic slug to its current URL via slug_history', function () {
    $clinic = Clinic::factory()->inCity($this->iasi)->create(['slug' => 'vechi-slug']);

    $clinic->update(['slug' => 'nou-slug']);

    $this->get('/clinica/vechi-slug')
        ->assertRedirect(route('clinic.show', $clinic))
        ->assertStatus(301);

    $this->get('/clinica/inexistent')->assertNotFound();
});

it('renders the county hub page', function () {
    seedImplantClinics($this->iasi, $this->implant, 1);

    $this->get(route('county.show', $this->iasi->county))
        ->assertOk()
        ->assertSee('județul '.$this->iasi->county->name, escape: false)
        ->assertSee($this->iasi->name, escape: false);
});

it('renders the nationwide service page linking to qualifying cities', function () {
    seedImplantClinics($this->iasi, $this->implant, 3);

    $this->get(route('service.show', $this->implant))
        ->assertOk()
        ->assertSee($this->iasi->name, escape: false);
});

it('marks noindex pages correctly via SeoMeta', function () {
    // The SeoMeta contract the /cauta page (Phase 6) will rely on.
    $meta = (new SeoMeta('X'))->noindex();
    expect($meta->isIndexable())->toBeFalse()
        ->and($meta->robots)->toBe('noindex,follow');
});
