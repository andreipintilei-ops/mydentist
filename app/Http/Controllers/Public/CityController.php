<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\CityServiceStat;
use App\Models\Clinic;
use App\Models\Service;
use App\Support\Seo\JsonLd\BreadcrumbListSchema;
use App\Support\Seo\JsonLd\ItemListSchema;
use App\Support\Seo\SeoMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class CityController extends Controller
{
    /**
     * /stomatologi/{oras}
     */
    public function show(City $city): View
    {
        $clinics = $this->publishedClinics($city);

        // Only services that clear the thin-content threshold get a facet link —
        // linking to a page that would 301 straight back is pointless.
        $services = CityServiceStat::query()
            ->with('service')
            ->where('city_id', $city->id)
            ->where('clinic_count', '>=', config('directory.thin_content_threshold'))
            ->orderByDesc('clinic_count')
            ->get();

        $crumbs = [
            ['name' => 'Acasă', 'url' => route('home')],
            ['name' => "Stomatologi în {$city->name}", 'url' => route('city.show', $city)],
        ];

        $seo = SeoMeta::make(
            "Stomatologi în {$city->name}",
            "Clinici și cabinete stomatologice în {$city->name}. Compară servicii, "
                .'program și recenzii, apoi programează-te la un dentist din apropiere.',
        )
            ->canonical($this->canonicalFor(route('city.show', $city), $clinics))
            ->jsonLd(ItemListSchema::make($clinics->getCollection(), "Stomatologi în {$city->name}"))
            ->jsonLd(BreadcrumbListSchema::make($crumbs));

        return view('public.city', compact('city', 'clinics', 'services', 'crumbs', 'seo'));
    }

    /**
     * /stomatologi/{oras}/{serviciu}
     *
     * Thin-content guard: below the configured clinic threshold this page 301s
     * to the city page rather than existing as a near-empty facet URL.
     */
    public function service(City $city, Service $service): View|RedirectResponse
    {
        $count = (int) CityServiceStat::query()
            ->where('city_id', $city->id)
            ->where('service_id', $service->id)
            ->value('clinic_count');

        if ($count < config('directory.thin_content_threshold')) {
            return redirect()->route('city.show', $city, 301);
        }

        $clinics = $this->publishedClinics($city, $service);

        $crumbs = [
            ['name' => 'Acasă', 'url' => route('home')],
            ['name' => $city->name, 'url' => route('city.show', $city)],
            ['name' => $service->name, 'url' => route('city.service', [$city, $service])],
        ];

        $description = $service->description
            ? "{$service->name} în {$city->name}. {$service->description}"
            : "Clinici din {$city->name} care oferă {$service->name}.";

        $seo = SeoMeta::make("{$service->name} în {$city->name}", $description)
            ->canonical($this->canonicalFor(route('city.service', [$city, $service]), $clinics))
            ->jsonLd(ItemListSchema::make($clinics->getCollection(), "{$service->name} în {$city->name}"))
            ->jsonLd(BreadcrumbListSchema::make($crumbs));

        return view('public.city-service', compact('city', 'service', 'clinics', 'crumbs', 'seo'));
    }

    /**
     * Published clinics in a city, optionally filtered to a service, paginated
     * at ?pagina=N with a stable order.
     *
     * @return LengthAwarePaginator<int, Clinic>
     */
    private function publishedClinics(City $city, ?Service $service = null): LengthAwarePaginator
    {
        return Clinic::query()
            ->published()
            ->inCity($city)
            ->when($service, fn ($q) => $q->whereHas('services', fn ($s) => $s->whereKey($service->id)))
            ->with(['city', 'services'])
            ->orderByDesc('rating_avg')
            ->orderByDesc('rating_count')
            ->orderBy('name')
            ->paginate(config('directory.per_page'), ['*'], 'pagina')
            ->withQueryString();
    }

    /**
     * Self-referencing canonical that INCLUDES ?pagina=N on deep pages (never
     * collapses paginated pages onto page 1) but stays clean on page 1.
     *
     * @param  LengthAwarePaginator<int, Clinic>  $clinics
     */
    private function canonicalFor(string $base, LengthAwarePaginator $clinics): string
    {
        return $clinics->currentPage() > 1
            ? $base.'?pagina='.$clinics->currentPage()
            : $base;
    }
}
