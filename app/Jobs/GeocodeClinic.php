<?php

namespace App\Jobs;

use App\Models\Clinic;
use App\Services\GeocodingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * Geocodes a clinic's address in the background. Dispatched on create/address
 * change (Phase 7). Until this succeeds the clinic keeps lat/lng = 0 and cannot
 * be published — a validation rule guards that, so an ungeocoded clinic never
 * shows up in the map or the ocean off West Africa.
 */
class GeocodeClinic implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public Clinic $clinic) {}

    /**
     * One geocode at a time — reinforces the GeocodingService 1 req/s throttle
     * even if several clinics are queued at once.
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('geocoding'))->expireAfter(120)];
    }

    public function handle(GeocodingService $geocoder): void
    {
        $coords = $geocoder->geocode($this->clinic->address, $this->clinic->city?->name);

        if ($coords === null) {
            // Leave coordinates at 0/0 so the clinic stays unpublishable and a
            // moderator can fix the address. Retrying may still help (transient
            // Nominatim errors), so let the job fail naturally to trigger retry.
            $this->fail("Could not geocode clinic #{$this->clinic->id}: {$this->clinic->address}");

            return;
        }

        $this->clinic->forceFill($coords)->save();
    }
}
