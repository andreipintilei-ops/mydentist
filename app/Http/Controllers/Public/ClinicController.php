<?php

namespace App\Http\Controllers\Public;

use App\Enums\ClinicStatus;
use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Support\Seo\JsonLd\BreadcrumbListSchema;
use App\Support\Seo\JsonLd\DentistSchema;
use App\Support\Seo\SeoMeta;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ClinicController extends Controller
{
    /**
     * /clinica/{slug}
     */
    public function show(Clinic $clinic): View
    {
        // Only published clinics are public. Draft/pending/rejected 404 even
        // though the slug resolves — those are not for the world yet.
        if ($clinic->status !== ClinicStatus::Published) {
            throw new NotFoundHttpException;
        }

        $clinic->load([
            'city.county',
            'services',
            'openingHours',
            'photos',
            'reviews' => fn ($q) => $q->published()->latest('published_at')->limit(10),
        ]);

        $crumbs = [
            ['name' => 'Acasă', 'url' => route('home')],
            ['name' => $clinic->city->name, 'url' => route('city.show', $clinic->city)],
            ['name' => $clinic->name, 'url' => route('clinic.show', $clinic)],
        ];

        $metaDescription = str($clinic->description ?: "{$clinic->name} — clinică stomatologică în {$clinic->city->name}.")
            ->stripTags()->squish()->limit(155)->value();

        $seo = SeoMeta::make($clinic->name, $metaDescription)
            ->canonical(route('clinic.show', $clinic))
            ->ogType('business.business')
            ->ogImage($clinic->photos->first()?->url)
            ->jsonLd(DentistSchema::make($clinic))
            ->jsonLd(BreadcrumbListSchema::make($crumbs));

        // Props for the locator-map island. Built here (not inline in Blade) so
        // it is a single @json() the Blade parser accepts. Null until geocoded.
        $mapProps = ($clinic->lat && $clinic->lng) ? [
            'lat' => (float) $clinic->lat,
            'lng' => (float) $clinic->lng,
            'name' => $clinic->name,
            'address' => $clinic->address,
        ] : null;

        return view('public.clinic', compact('clinic', 'crumbs', 'seo', 'mapProps'));
    }
}
