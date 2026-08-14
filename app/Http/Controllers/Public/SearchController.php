<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Clinic;
use App\Models\Service;
use App\Services\ClinicSearchService;
use App\Support\Seo\SeoMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * /cauta — the interactive map/filter app. NOINDEX: this is the application
     * surface, not an SEO page. Every indexable listing lives under the Blade
     * taxonomy (/stomatologi/...); keeping /cauta out of the index is what stops
     * infinite faceted URLs from eating the crawl budget.
     */
    public function index(Request $request): View
    {
        // Cities worth offering in the combobox: those with published clinics.
        $cities = City::query()
            ->select('id', 'name', 'slug', 'lat', 'lng')
            ->whereHas('clinics', fn ($q) => $q->published())
            ->orderBy('name')
            ->get();

        $services = Service::orderBy('sort_order')->get(['id', 'name', 'slug']);

        $seo = SeoMeta::make(
            'Caută stomatologi pe hartă',
            'Caută clinici stomatologice pe hartă și filtrează după oraș, serviciu, '
                .'contract CNAS, rating și program.',
        )->canonical(route('search'))->noindex();

        return view('public.search', [
            'seo' => $seo,
            'cities' => $cities,
            // Props handed to the Search island. Built here (not inline in Blade)
            // so it is a single @json() expression the Blade parser is happy with.
            'islandProps' => [
                'endpoint' => route('search.clinics'),
                'cities' => $cities,
                'services' => $services,
                'initial' => [
                    'city_id' => $request->integer('oras') ?: null,
                    'service_id' => $request->integer('serviciu') ?: null,
                ],
            ],
        ]);
    }

    /**
     * JSON feed for the map/list. Filtered by the query string; the map passes
     * its viewport as min_lat/min_lng/max_lat/max_lng on every move.
     */
    public function clinics(Request $request, ClinicSearchService $search): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'city_id' => ['nullable', 'integer'],
            'service_id' => ['nullable', 'integer'],
            'cnas' => ['nullable', 'boolean'],
            'open_now' => ['nullable', 'boolean'],
            'min_rating' => ['nullable', 'numeric', 'between:0,5'],
            'min_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'max_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'min_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'max_lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $clinics = $search->search($filters);

        return response()->json([
            'count' => $clinics->count(),
            'clinics' => $clinics->map(fn (Clinic $clinic) => [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'url' => route('clinic.show', $clinic),
                'lat' => (float) $clinic->lat,
                'lng' => (float) $clinic->lng,
                'address' => $clinic->address,
                'city' => $clinic->city?->name,
                'cnas' => $clinic->cnas_contract,
                'rating_avg' => (float) $clinic->rating_avg,
                'rating_count' => $clinic->rating_count,
                'services' => $clinic->services->pluck('name')->all(),
            ])->all(),
        ]);
    }
}
