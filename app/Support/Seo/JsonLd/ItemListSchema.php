<?php

namespace App\Support\Seo\JsonLd;

use App\Models\Clinic;
use Illuminate\Support\Collection;

/**
 * schema.org "ItemList" for a listing page (city, service, city × service …).
 * Each entry points at a clinic profile so the list reads as a curated set of
 * real entities rather than an opaque page of text.
 */
class ItemListSchema
{
    /**
     * @param  Collection<int, Clinic>  $clinics
     * @return array<string, mixed>
     */
    public static function make(Collection $clinics, ?string $name = null): array
    {
        if ($clinics->isEmpty()) {
            return [];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $name,
            'numberOfItems' => $clinics->count(),
            'itemListElement' => $clinics->values()->map(fn (Clinic $clinic, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'url' => route('clinic.show', $clinic),
                'name' => $clinic->name,
            ])->all(),
        ];
    }
}
