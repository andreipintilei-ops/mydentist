<?php

namespace App\Support\Seo\JsonLd;

/**
 * schema.org "BreadcrumbList". Feed it the same trail the visible breadcrumb
 * shows (Acasă › Iași › Implant dentar …) so the two never disagree.
 */
class BreadcrumbListSchema
{
    /**
     * @param  array<int, array{name: string, url: string}>  $crumbs
     * @return array<string, mixed>
     */
    public static function make(array $crumbs): array
    {
        if ($crumbs === []) {
            return [];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $crumb, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ], $crumbs, array_keys($crumbs)),
        ];
    }
}
