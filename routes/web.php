<?php

use App\Http\Controllers\Admin\ClaimController as AdminClaimController;
use App\Http\Controllers\Admin\ClinicController as AdminClinicController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\SeoToolsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\OwnerClinicController;
use App\Http\Controllers\Public\CityController;
use App\Http\Controllers\Public\ClaimController;
use App\Http\Controllers\Public\ClinicController;
use App\Http\Controllers\Public\ClinicRegistrationController;
use App\Http\Controllers\Public\CountyController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\SearchController;
use App\Http\Controllers\Public\ServiceController;
use App\Http\Routing\LegacySlugRedirect;
use App\Models\City;
use App\Models\Clinic;
use App\Models\County;
use App\Models\Service;
use Illuminate\Support\Facades\Route;

/*
| Public, server-rendered (Blade) SEO pages. This is the indexable taxonomy —
| everything here returns complete HTML on first byte. See docs/PLAN.md.
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

// robots.txt — disallow the noindex/app surfaces, point crawlers at the sitemap.
// Served dynamically so the Sitemap line is an absolute URL for any environment.
Route::get('robots.txt', function () {
    $body = implode("\n", [
        'User-agent: *',
        'Disallow: /cauta',
        'Disallow: /adauga-clinica',
        'Disallow: /admin',
        'Disallow: /cont',
        '',
        'Sitemap: '.url('/sitemap.xml'),
        '',
    ]);

    return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');

// Interactive search (noindex). The JSON feed backs the Vue map/list island.
Route::get('/cauta', [SearchController::class, 'index'])->name('search');
Route::get('/cauta/clinici', [SearchController::class, 'clinics'])->name('search.clinics');

// Public clinic registration (noindex). Submissions land as `pending`.
Route::get('/adauga-clinica', [ClinicRegistrationController::class, 'create'])->name('clinic.register');
Route::post('/adauga-clinica', [ClinicRegistrationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('clinic.register.store');

Route::get('/servicii/{service}', [ServiceController::class, 'show'])
    ->name('service.show')
    ->missing(LegacySlugRedirect::to(Service::class, 'service.show', 'service'));

Route::get('/clinica/{clinic}', [ClinicController::class, 'show'])
    ->name('clinic.show')
    ->missing(LegacySlugRedirect::to(Clinic::class, 'clinic.show', 'clinic'));

// Claim a listing. Requesting requires an account (that account becomes the
// owner on approval); the email link that verifies the request is public.
Route::middleware('auth')->group(function () {
    Route::get('/clinica/{clinic}/revendica', [ClaimController::class, 'create'])->name('claim.create');
    Route::post('/clinica/{clinic}/revendica', [ClaimController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('claim.store');
});
Route::get('/revendicare/{token}/confirma', [ClaimController::class, 'verify'])->name('claim.verify');

Route::prefix('stomatologi')->group(function () {
    // Declared before {city} so "judetul-*" is never captured as a city slug.
    Route::get('judetul-{county}', [CountyController::class, 'show'])
        ->name('county.show')
        ->missing(LegacySlugRedirect::to(County::class, 'county.show', 'county'));

    Route::get('{city}', [CityController::class, 'show'])
        ->name('city.show')
        ->missing(LegacySlugRedirect::to(City::class, 'city.show', 'city'));

    Route::get('{city}/{service}', [CityController::class, 'service'])
        ->name('city.service');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Owner panel: manage the clinics you've claimed. Bind by id (not the public
    // slug) — these are internal management URLs and the UI works with ids.
    Route::get('/cont', [OwnerClinicController::class, 'index'])->name('owner.clinics.index');
    Route::get('/cont/clinici/{clinic:id}/editeaza', [OwnerClinicController::class, 'edit'])->name('owner.clinics.edit');
    Route::put('/cont/clinici/{clinic:id}', [OwnerClinicController::class, 'update'])->name('owner.clinics.update');
});

// Admin dashboard — admins + moderators only (can.moderate gate).
Route::middleware(['auth', 'verified', 'can.moderate'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('moderare', [ModerationController::class, 'index'])->name('moderation.index');
    Route::post('moderare/{clinic:id}/aproba', [ModerationController::class, 'approve'])->name('moderation.approve');
    Route::post('moderare/{clinic:id}/respinge', [ModerationController::class, 'reject'])->name('moderation.reject');

    Route::get('revendicari', [AdminClaimController::class, 'index'])->name('claims.index');
    Route::post('revendicari/{claim}/aproba', [AdminClaimController::class, 'approve'])->name('claims.approve');
    Route::post('revendicari/{claim}/respinge', [AdminClaimController::class, 'reject'])->name('claims.reject');

    Route::get('recenzii', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::post('recenzii/{review}/aproba', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('recenzii/{review}/respinge', [AdminReviewController::class, 'reject'])->name('reviews.reject');

    Route::get('clinici', [AdminClinicController::class, 'index'])->name('clinics.index');
    Route::get('clinici/{clinic:id}/editeaza', [AdminClinicController::class, 'edit'])->name('clinics.edit');
    Route::put('clinici/{clinic:id}', [AdminClinicController::class, 'update'])->name('clinics.update');

    Route::get('utilizatori', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('utilizatori/{user}/rol', [AdminUserController::class, 'updateRole'])->name('users.role');

    Route::get('seo', [SeoToolsController::class, 'index'])->name('seo.index');
    Route::post('seo/rebuild-stats', [SeoToolsController::class, 'rebuildStats'])->name('seo.rebuild-stats');
    Route::post('seo/purge-demo', [SeoToolsController::class, 'purgeDemo'])->name('seo.purge-demo');
});

// Visual diff between the Blade UI mirrors and the real shadcn-vue components.
// Never exposed outside local development.
if (app()->environment('local')) {
    Route::view('_ui-parity', 'dev.ui-parity');
}

require __DIR__.'/settings.php';
