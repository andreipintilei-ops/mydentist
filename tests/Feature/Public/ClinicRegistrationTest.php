<?php

use App\Enums\ClinicStatus;
use App\Jobs\GeocodeClinic;
use App\Models\City;
use App\Models\Clinic;
use App\Models\Service;
use Database\Seeders\CitySeeder;
use Database\Seeders\CountySeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([CountySeeder::class, CitySeeder::class, ServiceSeeder::class]);
    $this->iasi = City::where('slug', 'iasi')->firstOrFail();
});

it('shows the registration form', function () {
    $this->get(route('clinic.register'))->assertOk()->assertSee('Adaugă clinica ta', escape: false);
});

it('creates a pending clinic, attaches services and queues geocoding', function () {
    Queue::fake();

    $implant = Service::where('slug', 'implant-dentar')->firstOrFail();

    $response = $this->post(route('clinic.register.store'), [
        'name' => 'Clinica Test SRL',
        'city_id' => $this->iasi->id,
        'address' => 'Strada Păcurari 10',
        'phone' => '0740111222',
        'email' => 'contact@clinicatest.ro',
        'services' => [$implant->id],
        'cnas_contract' => '1',
    ]);

    $response->assertRedirect(route('clinic.register'))->assertSessionHas('status');

    $clinic = Clinic::where('name', 'Clinica Test SRL')->firstOrFail();

    expect($clinic->status)->toBe(ClinicStatus::Pending)
        ->and($clinic->is_demo)->toBeFalse()
        ->and($clinic->cnas_contract)->toBeTrue()
        ->and($clinic->services)->toHaveCount(1);

    Queue::assertPushed(GeocodeClinic::class);
});

it('validates required fields', function () {
    $this->post(route('clinic.register.store'), [])
        ->assertSessionHasErrors(['name', 'city_id', 'address', 'phone', 'email']);
});

it('never publishes a registration directly', function () {
    $this->post(route('clinic.register.store'), [
        'name' => 'Instant Publish?',
        'city_id' => $this->iasi->id,
        'address' => 'Str. X 1',
        'phone' => '0700',
        'email' => 'a@b.ro',
    ]);

    expect(Clinic::where('name', 'Instant Publish?')->value('status'))->not->toBe(ClinicStatus::Published->value);
});
