<?php

use App\Enums\ClaimStatus;
use App\Enums\UserRole;
use App\Models\City;
use App\Models\Claim;
use App\Models\Clinic;
use App\Models\User;
use App\Notifications\VerifyClaimEmail;
use Database\Seeders\CitySeeder;
use Database\Seeders\CountySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([CountySeeder::class, CitySeeder::class]);
    $this->iasi = City::where('slug', 'iasi')->firstOrFail();
    $this->clinic = Clinic::factory()->inCity($this->iasi)->create();
});

it('redirects guests to login when claiming', function () {
    $this->get(route('claim.create', $this->clinic))->assertRedirect(route('login'));
});

it('lets an authenticated user open the claim form for an unclaimed clinic', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('claim.create', $this->clinic))
        ->assertOk()
        ->assertSee($this->clinic->name, escape: false);
});

it('forbids claiming an already-claimed clinic', function () {
    $owner = User::factory()->create();
    $this->clinic->update(['claimed_by' => $owner->id]);

    $this->actingAs(User::factory()->create())
        ->get(route('claim.create', $this->clinic))
        ->assertForbidden();
});

it('creates a pending claim and emails a verification link', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)->post(route('claim.store', $this->clinic), [
        'claimant_email' => 'owner@clinicatest.ro',
        'message' => 'Sunt medicul titular.',
    ])->assertRedirect(route('clinic.show', $this->clinic));

    $claim = Claim::firstOrFail();
    expect($claim->status)->toBe(ClaimStatus::Pending)
        ->and($claim->user_id)->toBe($user->id)
        ->and($claim->verification_token)->not->toBeNull();

    Notification::assertSentOnDemand(VerifyClaimEmail::class);
});

it('marks the claim verified from the email link', function () {
    $claim = Claim::create([
        'clinic_id' => $this->clinic->id,
        'user_id' => User::factory()->create()->id,
        'claimant_email' => 'owner@clinicatest.ro',
        'status' => ClaimStatus::Pending,
        'verification_token' => 'tok_'.str_repeat('a', 40),
    ]);

    $this->get(route('claim.verify', ['token' => $claim->verification_token]))
        ->assertRedirect(route('clinic.show', $this->clinic));

    $claim->refresh();
    expect($claim->status)->toBe(ClaimStatus::Verified)
        ->and($claim->verified_at)->not->toBeNull()
        ->and($claim->verification_token)->toBeNull();
});

it('assigns ownership and promotes the user on approval', function () {
    $user = User::factory()->create(); // default role: user
    $moderator = User::factory()->create();
    $moderator->forceFill(['role' => UserRole::Admin])->save();

    $claim = Claim::create([
        'clinic_id' => $this->clinic->id,
        'user_id' => $user->id,
        'claimant_email' => 'owner@clinicatest.ro',
        'status' => ClaimStatus::Verified,
        'verified_at' => now(),
    ]);

    $claim->approve($moderator);

    expect($this->clinic->fresh()->claimed_by)->toBe($user->id)
        ->and($user->fresh()->role)->toBe(UserRole::ClinicOwner)
        ->and($claim->fresh()->status)->toBe(ClaimStatus::Approved);
});
