<?php

namespace App\Http\Controllers\Public;

use App\Enums\ClinicStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterClinicRequest;
use App\Jobs\GeocodeClinic;
use App\Models\City;
use App\Models\Clinic;
use App\Models\Service;
use App\Support\Seo\SeoMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ClinicRegistrationController extends Controller
{
    /**
     * GET /adauga-clinica — the public "add your clinic" form.
     */
    public function create(): View
    {
        $seo = SeoMeta::make(
            'Adaugă clinica ta',
            'Înscrie-ți clinica stomatologică în director. Completează datele, iar '
                .'echipa noastră le verifică înainte de publicare.',
        )->canonical(route('clinic.register'))->noindex();

        return view('public.register-clinic', [
            'seo' => $seo,
            'cities' => City::query()->with('county:id,name')->orderBy('name')->get(),
            'services' => Service::orderBy('sort_order')->get(),
        ]);
    }

    /**
     * POST /adauga-clinica — create the clinic as `pending` for moderation.
     */
    public function store(RegisterClinicRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $clinic = Clinic::create([
            'name' => $data['name'],
            'slug' => Clinic::generateUniqueSlug($data['name']),
            'city_id' => $data['city_id'],
            'address' => $data['address'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'website' => $data['website'] ?? null,
            'description' => $data['description'] ?? null,
            'cnas_contract' => $request->boolean('cnas_contract'),
            // Awaiting moderation, never public until an admin approves it.
            'status' => ClinicStatus::Pending,
            // A logged-in submitter is provisionally the owner; anonymous ones
            // claim the listing later. Moderation confirms either way.
            'claimed_by' => $request->user()?->id,
        ]);

        if (! empty($data['services'])) {
            $clinic->services()->sync($data['services']);
        }

        // Turn the address into coordinates in the background (queued, 1 req/s).
        GeocodeClinic::dispatch($clinic);

        return redirect()
            ->route('clinic.register')
            ->with('status', 'Îți mulțumim! Clinica a fost trimisă spre verificare și va fi publicată după aprobare.');
    }
}
