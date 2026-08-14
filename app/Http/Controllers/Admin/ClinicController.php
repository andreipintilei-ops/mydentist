<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClinicStatus;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Clinic;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Support\ClinicHours;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClinicController extends Controller
{
    private const SORTABLE = ['name', 'created_at', 'rating_avg', 'status'];

    /**
     * GET /admin/clinici — server-side searchable/filterable/sortable table.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'created_at';
        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        $clinics = Clinic::withoutGlobalScope('excludeDemo')
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")))
            ->when($status !== '', fn (Builder $q) => $q->where('status', $status))
            ->with('city:id,name')
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Clinic $clinic) => [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'city' => $clinic->city?->name,
                'status' => $clinic->status->value,
                'status_label' => $clinic->status->label(),
                'rating_avg' => $clinic->rating_avg,
                'rating_count' => $clinic->rating_count,
                'is_demo' => $clinic->is_demo,
            ]);

        return Inertia::render('admin/clinics/Index', [
            'clinics' => $clinics,
            'filters' => ['q' => $search, 'status' => $status, 'sort' => $sort, 'dir' => $dir],
            'statuses' => collect(ClinicStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    /**
     * GET /admin/clinici/{clinic}/editeaza
     */
    public function edit(Clinic $clinic): Response
    {
        $clinic = Clinic::withoutGlobalScope('excludeDemo')->with(['services:id', 'openingHours'])->findOrFail($clinic->id);

        return Inertia::render('admin/clinics/Edit', [
            'clinic' => [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'status' => $clinic->status->value,
                'city_id' => $clinic->city_id,
                'address' => $clinic->address,
                'lat' => $clinic->lat,
                'lng' => $clinic->lng,
                'phone' => $clinic->phone,
                'email' => $clinic->email,
                'website' => $clinic->website,
                'description' => $clinic->description,
                'cnas_contract' => $clinic->cnas_contract,
            ],
            'cities' => City::orderBy('name')->get(['id', 'name']),
            'services' => Service::orderBy('sort_order')->get(['id', 'name']),
            'selectedServices' => $clinic->services->pluck('id')->all(),
            'hours' => ClinicHours::editorRows($clinic),
            'days' => OpeningHour::DAYS_RO,
            'statuses' => collect(ClinicStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    /**
     * PUT /admin/clinici/{clinic}
     */
    public function update(Request $request, Clinic $clinic): RedirectResponse
    {
        $clinic = Clinic::withoutGlobalScope('excludeDemo')->findOrFail($clinic->id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:'.implode(',', array_column(ClinicStatus::cases(), 'value'))],
            'city_id' => ['required', 'exists:cities,id'],
            'address' => ['required', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
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
            'name' => $validated['name'],
            'status' => $validated['status'],
            'city_id' => $validated['city_id'],
            'address' => $validated['address'],
            'lat' => $validated['lat'] ?? 0,
            'lng' => $validated['lng'] ?? 0,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'website' => $validated['website'] ?? null,
            'description' => $validated['description'] ?? null,
            'cnas_contract' => $request->boolean('cnas_contract'),
        ]);

        $clinic->services()->sync($validated['services'] ?? []);
        ClinicHours::replace($clinic, $validated['hours'] ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Clinica a fost salvată.']);

        return to_route('admin.clinics.edit', $clinic);
    }
}
