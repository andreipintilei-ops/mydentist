<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Forward-geocodes Romanian addresses to coordinates using Nominatim
 * (OpenStreetMap). Nominatim's usage policy requires an identifying User-Agent
 * and a hard cap of 1 request/second, and forbids hammering it for bulk work —
 * so results are cached aggressively and every call is spaced out. Real bulk
 * imports should use a self-hosted Nominatim or a paid provider; this is for the
 * trickle of self-registered clinics.
 */
class GeocodingService
{
    private const ENDPOINT = 'https://nominatim.openstreetmap.org/search';

    private const CACHE_TTL_DAYS = 30;

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function geocode(string $address, ?string $city = null): ?array
    {
        $query = trim($address.($city ? ", {$city}" : '').', România');

        $cacheKey = 'geocode:'.sha1(mb_strtolower($query));

        // A "null" (not-found) result is cached as false so we don't re-query a
        // bad address on every retry; Cache::remember treats null as a miss.
        $result = Cache::remember($cacheKey, now()->addDays(self::CACHE_TTL_DAYS), function () use ($query) {
            $this->throttle();

            $response = Http::withHeaders([
                'User-Agent' => config('app.name', 'MyDentist').' geocoder ('.config('app.url').')',
            ])->timeout(15)->get(self::ENDPOINT, [
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => 1,
                'countrycodes' => 'ro',
            ]);

            $hit = $response->successful() ? ($response->json()[0] ?? null) : null;

            if (! $hit) {
                return false;
            }

            return [
                'lat' => round((float) $hit['lat'], 7),
                'lng' => round((float) $hit['lon'], 7),
            ];
        });

        return $result ?: null;
    }

    /**
     * Enforce Nominatim's 1 req/s ceiling across whatever process is geocoding.
     * A cache-stored timestamp is good enough because geocoding runs on a single
     * queue worker; sleep out the remainder of the second if we're too eager.
     */
    private function throttle(): void
    {
        $last = (float) Cache::get('geocode:last_request_at', 0);
        $elapsed = microtime(true) - $last;

        if ($elapsed < 1.0) {
            usleep((int) ((1.0 - $elapsed) * 1_000_000));
        }

        Cache::put('geocode:last_request_at', microtime(true), now()->addMinutes(5));
    }
}
