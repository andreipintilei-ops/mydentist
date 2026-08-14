<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Clinic;
use App\Models\Service;
use App\Support\Seo\SeoMeta;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // Cities that actually have published clinics, most first — no point
        // linking to empty landing pages from the homepage.
        $cities = City::query()
            ->withCount(['clinics' => fn ($q) => $q->published()])
            ->having('clinics_count', '>', 0)
            ->orderByDesc('clinics_count')
            ->take(12)
            ->get();

        $services = Service::orderBy('sort_order')->get();

        $featured = Clinic::query()
            ->published()
            ->with(['city', 'services'])
            ->where('rating_count', '>', 0)
            ->orderByDesc('rating_avg')
            ->orderByDesc('rating_count')
            ->take(6)
            ->get();

        $seo = SeoMeta::make(
            'Găsește un stomatolog de încredere în România',
            'Director de clinici și cabinete stomatologice din România. Caută după oraș și '
                .'specializare — implant dentar, ortodonție, albire și altele.',
        )->canonical(route('home'));

        return view('public.home', compact('cities', 'services', 'featured', 'seo'));
    }
}
