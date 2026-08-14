<?php

namespace App\Support\Seo\JsonLd;

use App\Models\Clinic;

/**
 * schema.org "Dentist" (a LocalBusiness / MedicalBusiness) for a clinic profile.
 * Google's Rich Results read this to show the clinic as a local entity.
 *
 * aggregateRating is emitted ONLY when real published reviews exist — inventing
 * a rating (or emitting one for a clinic with zero reviews) is a structured-data
 * spam signal and can earn a manual penalty.
 */
class DentistSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Clinic $clinic): array
    {
        $graph = [
            '@context' => 'https://schema.org',
            '@type' => 'Dentist',
            'name' => $clinic->name,
            'url' => route('clinic.show', $clinic),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $clinic->address,
                'addressLocality' => $clinic->city?->name,
                'addressRegion' => $clinic->city?->county?->name,
                'addressCountry' => 'RO',
            ],
        ];

        if ($clinic->lat && $clinic->lng) {
            $graph['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => $clinic->lat,
                'longitude' => $clinic->lng,
            ];
        }

        if ($clinic->phone) {
            $graph['telephone'] = $clinic->phone;
        }

        if ($clinic->website) {
            $graph['sameAs'] = [$clinic->website];
        }

        if ($clinic->description) {
            $graph['description'] = $clinic->description;
        }

        $image = $clinic->photos->first();
        if ($image) {
            $graph['image'] = $image->url;
        }

        $hours = self::openingHours($clinic);
        if ($hours !== []) {
            $graph['openingHoursSpecification'] = $hours;
        }

        if ($clinic->rating_count > 0) {
            $graph['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $clinic->rating_avg,
                'reviewCount' => $clinic->rating_count,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        return $graph;
    }

    /**
     * schema.org day names are English; our day_of_week is 0=Monday..6=Sunday.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function openingHours(Clinic $clinic): array
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        return $clinic->openingHours
            ->filter(fn ($h) => ! $h->is_closed && $h->opens_at && $h->closes_at)
            ->map(fn ($h) => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => 'https://schema.org/'.($days[$h->day_of_week] ?? 'Monday'),
                'opens' => substr((string) $h->opens_at, 0, 5),
                'closes' => substr((string) $h->closes_at, 0, 5),
            ])
            ->values()
            ->all();
    }
}
