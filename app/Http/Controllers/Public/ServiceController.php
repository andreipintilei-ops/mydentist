<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CityServiceStat;
use App\Models\Service;
use App\Support\Seo\JsonLd\BreadcrumbListSchema;
use App\Support\Seo\SeoMeta;
use Illuminate\Contracts\View\View;

class ServiceController extends Controller
{
    /**
     * /servicii/{serviciu}
     *
     * A nationwide hub for one specialty, linking to the city × service pages
     * that clear the thin-content threshold.
     */
    public function show(Service $service): View
    {
        // Cities offering this service, above the thin-content threshold, so we
        // only link to city × service pages that actually render.
        $cities = CityServiceStat::query()
            ->with('city.county')
            ->where('service_id', $service->id)
            ->where('clinic_count', '>=', config('directory.thin_content_threshold'))
            ->orderByDesc('clinic_count')
            ->get();

        $crumbs = [
            ['name' => 'Acasă', 'url' => route('home')],
            ['name' => $service->name, 'url' => route('service.show', $service)],
        ];

        $seo = SeoMeta::make(
            "{$service->name} — clinici stomatologice în România",
            $service->description ?: "Găsește clinici care oferă {$service->name} în orașul tău.",
        )
            ->canonical(route('service.show', $service))
            ->jsonLd(BreadcrumbListSchema::make($crumbs));

        return view('public.service', compact('service', 'cities', 'crumbs', 'seo'));
    }
}
