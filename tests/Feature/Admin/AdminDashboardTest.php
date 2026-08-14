<?php

use App\Enums\ClaimStatus;
use App\Enums\ClinicStatus;
use App\Enums\UserRole;
use App\Models\City;
use App\Models\Claim;
use App\Models\Clinic;
use App\Models\Service;
use App\Models\User;
use App\Notifications\ClinicModerated;
use Database\Seeders\CitySeeder;
use Database\Seeders\CountySeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([CountySeeder::class, CitySeeder::class, ServiceSeeder::class]);
    $this->iasi = City::where('slug', 'iasi')->firstOrFail();

    $this->admin = User::factory()->create();
    $this->admin->forceFill(['role' => UserRole::Admin])->save();

    $this->moderator = User::factory()->create();
    $this->moderator->forceFill(['role' => UserRole::Moderator])->save();
});

// ---- Gate --------------------------------------------------------------

it('blocks regular users and guests from /admin', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(); // guest -> login

    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('lets moderators and admins reach the dashboard', function () {
    $this->actingAs($this->moderator)->get(route('admin.dashboard'))
        ->assertOk()->assertInertia(fn ($p) => $p->component('admin/Dashboard')->has('kpis'));
});

// ---- Moderation --------------------------------------------------------

it('publishes a pending clinic and notifies the submitter', function () {
    Notification::fake();

    $clinic = Clinic::factory()->inCity($this->iasi)->status(ClinicStatus::Pending)
        ->create(['email' => 'owner@clinic.ro']);

    $this->actingAs($this->admin)
        ->post(route('admin.moderation.approve', $clinic))
        ->assertRedirect();

    expect($clinic->fresh()->status)->toBe(ClinicStatus::Published)
        ->and($clinic->fresh()->verified_at)->not->toBeNull();

    Notification::assertSentOnDemand(ClinicModerated::class);
});

it('rejects a pending clinic', function () {
    Notification::fake();

    $clinic = Clinic::factory()->inCity($this->iasi)->status(ClinicStatus::Pending)->create();

    $this->actingAs($this->admin)
        ->post(route('admin.moderation.reject', $clinic), ['reason' => 'Date incomplete'])
        ->assertRedirect();

    expect($clinic->fresh()->status)->toBe(ClinicStatus::Rejected);
});

// ---- Claims ------------------------------------------------------------

it('approves a claim, assigning ownership', function () {
    $user = User::factory()->create();
    $clinic = Clinic::factory()->inCity($this->iasi)->create();
    $claim = Claim::create([
        'clinic_id' => $clinic->id, 'user_id' => $user->id,
        'claimant_email' => 'o@c.ro', 'status' => ClaimStatus::Verified, 'verified_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.claims.approve', $claim))
        ->assertRedirect();

    expect($clinic->fresh()->claimed_by)->toBe($user->id)
        ->and($user->fresh()->role)->toBe(UserRole::ClinicOwner);
});

// ---- Reviews -----------------------------------------------------------

it('publishes a review and recalculates the rating', function () {
    $clinic = Clinic::factory()->inCity($this->iasi)->create();
    $review = $clinic->reviews()->create(['author_name' => 'Ana', 'rating' => 4, 'status' => 'pending']);

    $this->actingAs($this->moderator)
        ->post(route('admin.reviews.approve', $review))
        ->assertRedirect();

    expect($review->fresh()->status)->toBe('published')
        ->and($clinic->fresh()->rating_count)->toBe(1)
        ->and($clinic->fresh()->rating_avg)->toBe(4.0);
});

// ---- Clinics table + edit ---------------------------------------------

it('filters the clinics table by status', function () {
    Clinic::factory()->count(2)->inCity($this->iasi)->status(ClinicStatus::Published)->create();
    Clinic::factory()->inCity($this->iasi)->status(ClinicStatus::Pending)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.clinics.index', ['status' => 'pending']))
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('admin/clinics/Index')->has('clinics.data', 1));
});

// Regression: the admin/owner clinic routes bind by id, not the public slug —
// the Vue pages link with clinic.id, so a slug-bound route 404s from the UI.
it('loads the edit page via its literal id URL', function () {
    $clinic = Clinic::factory()->inCity($this->iasi)->create();

    $this->actingAs($this->admin)
        ->get("/admin/clinici/{$clinic->id}/editeaza")
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('admin/clinics/Edit'));
});

it('approves a pending clinic via its literal id URL', function () {
    $clinic = Clinic::factory()->inCity($this->iasi)->status(ClinicStatus::Pending)->create();

    $this->actingAs($this->admin)
        ->post("/admin/moderare/{$clinic->id}/aproba")
        ->assertRedirect();

    expect($clinic->fresh()->status)->toBe(ClinicStatus::Published);
});

it('lets an admin edit a clinic including status and coordinates', function () {
    $implant = Service::where('slug', 'implant-dentar')->firstOrFail();
    $clinic = Clinic::factory()->inCity($this->iasi)->status(ClinicStatus::Pending)->create();

    $this->actingAs($this->admin)->put(route('admin.clinics.update', $clinic), [
        'name' => 'Nume Nou',
        'status' => 'published',
        'city_id' => $this->iasi->id,
        'address' => 'Str. Nouă 5',
        'lat' => 47.16,
        'lng' => 27.58,
        'phone' => '0700',
        'email' => 'a@b.ro',
        'cnas_contract' => true,
        'services' => [$implant->id],
        'hours' => [['day_of_week' => 0, 'is_closed' => false, 'opens_at' => '09:00', 'closes_at' => '17:00']],
    ])->assertRedirect();

    $clinic->refresh();
    expect($clinic->name)->toBe('Nume Nou')
        ->and($clinic->status)->toBe(ClinicStatus::Published)
        ->and((float) $clinic->lat)->toBe(47.16)
        ->and($clinic->services)->toHaveCount(1);
});

// ---- Users -------------------------------------------------------------

it('lets an admin assign roles but forbids moderators', function () {
    $target = User::factory()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.users.role', $target), ['role' => 'moderator'])
        ->assertRedirect();
    expect($target->fresh()->role)->toBe(UserRole::Moderator);

    $this->actingAs($this->moderator)
        ->patch(route('admin.users.role', $target), ['role' => 'admin'])
        ->assertForbidden();
});

// ---- SEO tools ---------------------------------------------------------

it('rebuilds stats and purges demo clinics from the SEO tools', function () {
    Clinic::factory()->count(2)->inCity($this->iasi)->demo()->create();

    $this->actingAs($this->admin)->post(route('admin.seo.rebuild-stats'))->assertRedirect();
    $this->actingAs($this->admin)->post(route('admin.seo.purge-demo'))->assertRedirect();

    expect(Clinic::withoutGlobalScope('excludeDemo')->where('is_demo', true)->count())->toBe(0);
});
