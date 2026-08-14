<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Support\Seo\JsonLd\BreadcrumbListSchema;
use App\Support\Seo\SeoMeta;
use Illuminate\Contracts\View\View;

class CountyController extends Controller
{
    /**
     * /stomatologi/judetul-{judet}
     *
     * A county page is a hub that links out to its cities' listing pages rather
     * than a giant clinic dump — it distributes crawl equity to the city pages
     * that actually rank.
     */
    public function show(County $county): View
    {
        $cities = $county->cities()
            ->withCount(['clinics' => fn ($q) => $q->published()])
            ->having('clinics_count', '>', 0)
            ->orderByDesc('clinics_count')
            ->orderBy('name')
            ->get();

        $totalClinics = $cities->sum('clinics_count');

        $crumbs = [
            ['name' => 'Acasă', 'url' => route('home')],
            ['name' => "Județul {$county->name}", 'url' => route('county.show', $county)],
        ];

        $seo = SeoMeta::make(
            "Stomatologi în județul {$county->name}",
            "Clinici și cabinete stomatologice din județul {$county->name}. "
                .'Alege orașul și găsește un dentist potrivit.',
        )
            ->canonical(route('county.show', $county))
            ->jsonLd(BreadcrumbListSchema::make($crumbs));

        return view('public.county', compact('county', 'cities', 'totalClinics', 'crumbs', 'seo'));
    }
}
