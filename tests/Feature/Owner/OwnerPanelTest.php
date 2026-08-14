<?php

use App\Enums\UserRole;
use App\Models\City;
use App\Models\Clinic;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\CitySeeder;
use Database\Seeders\CountySeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([CountySeeder::class, CitySeeder::class, ServiceSeeder::class]);
    $this->iasi = City::where('slug', 'iasi')->firstOrFail();
    $this->owner = User::factory()->create();
    $this->clinic = Clinic::factory()->inCity($this->iasi)->create(['claimed_by' => $this->owner->id]);
});

it('lists only the clinics the user owns', function () {
    Clinic::factory()->inCity($this->iasi)->create(); // someone else's

    $this->actingAs($this->owner)
        ->get(route('owner.clinics.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('owner/Index')
            ->has('clinics', 1)
            ->where('clinics.0.id', $this->clinic->id));
});

it('lets the owner open the edit page', function () {
    $this->actingAs($this->owner)
        ->get(route('owner.clinics.edit', $this->clinic))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('owner/Edit')->where('clinic.id', $this->clinic->id));
});

// Regression: the owner route binds by id (not the public slug); the Vue links
// use clinic.id, so a slug-bound route would 404 from the UI.
it('loads the owner edit page via its literal id URL', function () {
    $this->actingAs($this->owner)
        ->get("/cont/clinici/{$this->clinic->id}/editeaza")
        ->assertOk();
});

it('forbids editing a clinic you do not own', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('owner.clinics.edit', $this->clinic))
        ->assertForbidden();
});

it('lets a moderator edit any clinic', function () {
    $mod = User::factory()->create();
    $mod->forceFill(['role' => UserRole::Moderator])->save();

    $this->actingAs($mod)
        ->get(route('owner.clinics.edit', $this->clinic))
        ->assertOk();
});

it('saves description, services and opening hours', function () {
    $implant = Service::where('slug', 'implant-dentar')->firstOrFail();

    $this->actingAs($this->owner)->put(route('owner.clinics.update', $this->clinic), [
        'description' => 'Descriere nouă a clinicii.',
        'phone' => '0740999888',
        'email' => 'nou@clinica.ro',
        'website' => 'https://clinica.ro',
        'cnas_contract' => true,
        'services' => [$implant->id],
        'hours' => [
            ['day_of_week' => 0, 'is_closed' => false, 'opens_at' => '08:30', 'closes_at' => '17:00'],
            ['day_of_week' => 6, 'is_closed' => true, 'opens_at' => null, 'closes_at' => null],
        ],
    ])->assertRedirect(route('owner.clinics.edit', $this->clinic));

    $clinic = $this->clinic->fresh();
    expect($clinic->description)->toBe('Descriere nouă a clinicii.')
        ->and($clinic->cnas_contract)->toBeTrue()
        ->and($clinic->services)->toHaveCount(1);

    $monday = OpeningHour::where('clinic_id', $clinic->id)->where('day_of_week', 0)->first();
    expect((string) $monday->opens_at)->toContain('08:30');

    $sunday = OpeningHour::where('clinic_id', $clinic->id)->where('day_of_week', 6)->first();
    expect($sunday->is_closed)->toBeTrue();
});

it('rejects an invalid website on update', function () {
    $this->actingAs($this->owner)->put(route('owner.clinics.update', $this->clinic), [
        'website' => 'not-a-url',
        'cnas_contract' => false,
    ])->assertSessionHasErrors('website');
});
