<?php

namespace App\Services;

use App\Enums\ClinicStatus;
use App\Models\City;
use App\Models\CityServiceStat;
use App\Models\Clinic;
use App\Models\County;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Builds the XML sitemap set. Only the indexable taxonomy goes in — /cauta,
 * /admin, /cont and any is_demo clinic are excluded by construction, so a
 * fictional practice can never end up in the index even if the production global
 * scope were somehow bypassed.
 *
 * The demo exclusion is written explicitly (not left to the env-based global
 * scope) so sitemaps are correct in every environment.
 */
class SitemapGenerator
{
    /** Max URLs per clinic sitemap chunk (spec allows 50k; we stay well under). */
    private const CHUNK = 10000;

    /**
     * Build every sitemap file as [filename => xml]. The command writes these to
     * public/; tests inspect the strings directly.
     *
     * @return array<string, string>
     */
    public function build(): array
    {
        $files = [
            'sitemap-orase.xml' => $this->urlset($this->geographyUrls()),
            'sitemap-servicii.xml' => $this->urlset($this->serviceUrls()),
        ];

        foreach ($this->clinicChunks() as $i => $urls) {
            $files['sitemap-clinici-'.($i + 1).'.xml'] = $this->urlset($urls);
        }

        $files['sitemap.xml'] = $this->index(array_keys($files));

        return $files;
    }

    /**
     * Write every sitemap to public/ and return the filenames written.
     *
     * @return array<int, string>
     */
    public function write(): array
    {
        $files = $this->build();

        foreach ($files as $name => $xml) {
            file_put_contents(public_path($name), $xml);
        }

        return array_keys($files);
    }

    /**
     * Home + counties + cities + qualifying city × service pages.
     *
     * @return array<int, array{loc: string, lastmod: ?string}>
     */
    private function geographyUrls(): array
    {
        $urls = [['loc' => route('home'), 'lastmod' => null]];

        // Counties with at least one published, non-demo clinic.
        County::query()
            ->whereHas('cities.clinics', fn ($q) => $this->publishedScope($q))
            ->get()
            ->each(fn (County $county) => $urls[] = ['loc' => route('county.show', $county), 'lastmod' => null]);

        // Cities with published clinics.
        City::query()
            ->whereHas('clinics', fn ($q) => $this->publishedScope($q))
            ->get()
            ->each(fn (City $city) => $urls[] = ['loc' => route('city.show', $city), 'lastmod' => null]);

        // city × service pages above the thin-content threshold.
        $threshold = (int) config('directory.thin_content_threshold');
        CityServiceStat::query()
            ->with(['city:id,slug', 'service:id,slug'])
            ->where('clinic_count', '>=', $threshold)
            ->get()
            ->each(function ($stat) use (&$urls) {
                if ($stat->city && $stat->service) {
                    $urls[] = ['loc' => route('city.service', [$stat->city, $stat->service]), 'lastmod' => null];
                }
            });

        return $urls;
    }

    /**
     * Service hubs that have at least one qualifying city (otherwise the page is
     * thin and not worth indexing).
     *
     * @return array<int, array{loc: string, lastmod: ?string}>
     */
    private function serviceUrls(): array
    {
        $threshold = (int) config('directory.thin_content_threshold');

        $serviceIds = CityServiceStat::query()
            ->where('clinic_count', '>=', $threshold)
            ->distinct()
            ->pluck('service_id');

        return Service::whereIn('id', $serviceIds)
            ->get()
            ->map(fn (Service $service) => ['loc' => route('service.show', $service), 'lastmod' => null])
            ->all();
    }

    /**
     * Published, non-demo clinics chunked into groups of CHUNK.
     *
     * @return array<int, array<int, array{loc: string, lastmod: ?string}>>
     */
    private function clinicChunks(): array
    {
        $urls = Clinic::query()
            ->where('status', ClinicStatus::Published->value)
            ->where('is_demo', false)
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->map(fn (Clinic $clinic) => [
                'loc' => route('clinic.show', $clinic),
                'lastmod' => $clinic->updated_at?->toAtomString(),
            ]);

        return $urls->isEmpty()
            ? [[]] // always emit at least one (empty) clinic sitemap
            : $urls->chunk(self::CHUNK)->map->values()->map->all()->values()->all();
    }

    /**
     * @param  Builder<Clinic>  $query
     */
    private function publishedScope($query): void
    {
        $query->where('status', ClinicStatus::Published->value)->where('is_demo', false);
    }

    /**
     * @param  array<int, array{loc: string, lastmod: ?string}>  $urls
     */
    private function urlset(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.htmlspecialchars($url['loc'], ENT_XML1).'</loc>';
            if (! empty($url['lastmod'])) {
                $xml .= '<lastmod>'.$url['lastmod'].'</lastmod>';
            }
            $xml .= '</url>'."\n";
        }

        return $xml.'</urlset>'."\n";
    }

    /**
     * @param  array<int, string>  $filenames
     */
    private function index(array $filenames): string
    {
        $now = Carbon::now()->toAtomString();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($filenames as $name) {
            $xml .= '  <sitemap><loc>'.htmlspecialchars(url($name), ENT_XML1).'</loc>'
                .'<lastmod>'.$now.'</lastmod></sitemap>'."\n";
        }

        return $xml.'</sitemapindex>'."\n";
    }
}
