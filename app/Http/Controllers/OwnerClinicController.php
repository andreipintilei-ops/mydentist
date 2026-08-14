<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Support\ClinicHours;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OwnerClinicController extends Controller
{
    /**
     * GET /cont — the clinics this user owns (via an approved claim).
     */
    public function index(Request $request): Response
    {
        $clinics = $request->user()->ownedClinics()
            ->with('city:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Clinic $clinic) => [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'slug' => $clinic->slug,
                'status' => $clinic->status->value,
                'status_label' => $clinic->status->label(),
                'city' => $clinic->city?->name,
                'public_url' => route('clinic.show', $clinic),
            ]);

        return Inertia::render('owner/Index', ['clinics' => $clinics]);
    }

    /**
     * GET /cont/clinici/{clinic}/editeaza — edit form.
     */
    public function edit(Clinic $clinic): Response
    {
        Gate::authorize('manage', $clinic);

        $clinic->load(['services:id', 'openingHours']);

        return Inertia::render('owner/Edit', [
            'clinic' => [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'status_label' => $clinic->status->label(),
                'description' => $clinic->description,
                'phone' => $clinic->phone,
                'email' => $clinic->email,
                'website' => $clinic->website,
                'cnas_contract' => $clinic->cnas_contract,
            ],
            'services' => Service::orderBy('sort_order')->get(['id', 'name']),
            'selectedServices' => $clinic->services->pluck('id')->all(),
            'hours' => ClinicHours::editorRows($clinic),
            'days' => OpeningHour::DAYS_RO,
        ]);
    }

    /**
     * PUT /cont/clinici/{clinic} — save owner edits.
     */
    public function update(Request $request, Clinic $clinic): RedirectResponse
    {
        Gate::authorize('manage', $clinic);

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'cnas_contract' => ['boolean'],
            'services' => ['array'],
            'services.*' => ['integer', 'exists:services,id'],
            'hours' => ['array'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'hours.*.is_closed' => ['boolean'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i'],
        ]);

        $clinic->update([
            'description' => $validated['description'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'website' => $validated['website'] ?? null,
            'cnas_contract' => $request->boolean('cnas_contract'),
        ]);

        $clinic->services()->sync($validated['services'] ?? []);

        ClinicHours::replace($clinic, $validated['hours'] ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Clinica a fost actualizată.']);

        return to_route('owner.clinics.edit', $clinic);
    }
}
