<?php

namespace App\Services;

use App\Models\Clinic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Builds the /cauta search query from a set of filters. Kept out of the
 * controller so the same logic backs the map JSON endpoint today and the sitemap
 * / API tomorrow. Only published (and, in production, non-demo — via the Clinic
 * global scope) clinics are ever returned.
 */
class ClinicSearchService
{
    /** Hard cap so a zoomed-out map can never try to render the whole country. */
    private const MAX_RESULTS = 300;

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Clinic>
     */
    public function search(array $filters): Collection
    {
        return $this->query($filters)
            ->with(['city:id,name,slug', 'services:id,name'])
            ->limit(self::MAX_RESULTS)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Clinic>
     */
    public function query(array $filters): Builder
    {
        return Clinic::query()
            ->published()
            ->when(! empty($filters['city_id']), fn (Builder $q) => $q->where('city_id', (int) $filters['city_id']))
            ->when(! empty($filters['service_id']), fn (Builder $q) => $q->whereHas(
                'services', fn (Builder $s) => $s->whereKey((int) $filters['service_id'])
            ))
            ->when(! empty($filters['cnas']), fn (Builder $q) => $q->where('cnas_contract', true))
            ->when(! empty($filters['min_rating']), fn (Builder $q) => $q->where('rating_avg', '>=', (float) $filters['min_rating']))
            ->when(! empty($filters['open_now']), fn (Builder $q) => $this->applyOpenNow($q))
            ->when(! empty($filters['q']), fn (Builder $q) => $this->applyText($q, (string) $filters['q']))
            ->when($this->hasBounds($filters), fn (Builder $q) => $q->inBounds(
                (float) $filters['min_lat'],
                (float) $filters['min_lng'],
                (float) $filters['max_lat'],
                (float) $filters['max_lng'],
            ))
            ->orderByDesc('rating_avg')
            ->orderByDesc('rating_count')
            ->orderBy('name');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function hasBounds(array $filters): bool
    {
        return isset($filters['min_lat'], $filters['min_lng'], $filters['max_lat'], $filters['max_lng']);
    }

    /**
     * "Deschis acum": the clinic has an opening-hours row for the current RO
     * weekday whose window contains the current RO time. Hours are stored in
     * local clinic time, so we ask Carbon for the time in Europe/Bucharest — not
     * the app's UTC — or every clinic would look shut for two hours.
     *
     * @param  Builder<Clinic>  $query
     */
    private function applyOpenNow(Builder $query): void
    {
        $now = Carbon::now('Europe/Bucharest');
        $weekday = ($now->dayOfWeek + 6) % 7; // Carbon 0=Sun..6=Sat -> our 0=Mon..6=Sun
        $time = $now->format('H:i:s');

        $query->whereHas('openingHours', function (Builder $hours) use ($weekday, $time) {
            $hours->where('day_of_week', $weekday)
                ->where('is_closed', false)
                ->where('opens_at', '<=', $time)
                ->where('closes_at', '>=', $time);
        });
    }

    /**
     * v1 text search: a LIKE across name/address. The utf8mb4 collation makes it
     * accent- and case-insensitive, so "iasi" matches "Iași". No typo tolerance —
     * a deliberate v1 limit; swap in Scout + Meilisearch if it bites.
     *
     * @param  Builder<Clinic>  $query
     */
    private function applyText(Builder $query, string $term): void
    {
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], trim($term)).'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('address', 'like', $like);
        });
    }
}
