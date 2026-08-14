<?php

use App\Enums\ClinicStatus;
use App\Models\City;
use App\Models\Clinic;
use App\Models\Service;
use App\Services\SitemapGenerator;
use Database\Seeders\CitySeeder;
use Database\Seeders\CountySeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([CountySeeder::class, CitySeeder::class, ServiceSeeder::class]);
    $this->iasi = City::where('slug', 'iasi')->firstOrFail();
    $this->implant = Service::where('slug', 'implant-dentar')->firstOrFail();
});

function sitemaps(): array
{
    return app(SitemapGenerator::class)->build();
}

it('lists published, non-demo clinics and excludes demo + unpublished', function () {
    $published = Clinic::factory()->inCity($this->iasi)->create(['slug' => 'real-published']);
    $demo = Clinic::factory()->inCity($this->iasi)->demo()->create(['slug' => 'demo-clinic']);
    $pending = Clinic::factory()->inCity($this->iasi)->status(ClinicStatus::Pending)->create(['slug' => 'pending-clinic']);

    $clinics = sitemaps()['sitemap-clinici-1.xml'];

    expect($clinics)->toContain(route('clinic.show', $published))
        ->and($clinics)->not->toContain('demo-clinic')
        ->and($clinics)->not->toContain('pending-clinic');
});

it('emits a valid sitemap index referencing the sub-sitemaps', function () {
    $index = sitemaps()['sitemap.xml'];

    expect($index)->toContain('<sitemapindex')
        ->and($index)->toContain(url('sitemap-orase.xml'))
        ->and($index)->toContain(url('sitemap-servicii.xml'))
        ->and($index)->toContain(url('sitemap-clinici-1.xml'));
});

it('includes city and city × service pages once they clear the threshold', function () {
    $clinics = Clinic::factory()->count(3)->inCity($this->iasi)->create();
    $clinics->each(fn (Clinic $c) => $c->services()->attach($this->implant));

    $this->artisan('stats:rebuild-city-service');

    $geo = sitemaps()['sitemap-orase.xml'];

    expect($geo)->toContain(route('city.show', $this->iasi))
        ->toContain(route('city.service', [$this->iasi, $this->implant]))
        ->toContain(route('home'));
});

it('carries a lastmod on clinic entries', function () {
    Clinic::factory()->inCity($this->iasi)->create();

    expect(sitemaps()['sitemap-clinici-1.xml'])->toContain('<lastmod>');
});

it('serves robots.txt disallowing the noindex surfaces and pointing at the sitemap', function () {
    $response = $this->get('/robots.txt');

    $response->assertOk()
        ->assertHeader('content-type', 'text/plain; charset=UTF-8')
        ->assertSee('Disallow: /cauta', escape: false)
        ->assertSee('Disallow: /admin', escape: false)
        ->assertSee('Disallow: /cont', escape: false)
        ->assertSee('Sitemap: '.url('/sitemap.xml'), escape: false);
});

it('renders a branded 404 page', function () {
    $this->get('/clinica/aceasta-nu-exista')
        ->assertNotFound()
        ->assertSee('Pagina nu a fost găsită', escape: false);
});
