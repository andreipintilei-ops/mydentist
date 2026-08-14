<x-layouts.public :seo="$seo">
    {{--
        The interactive search app. This page is NOINDEX (see SeoMeta->noindex()
        in SearchController) — it is the application, not an SEO surface. All
        indexable content lives under the Blade taxonomy.

        The Search island mounts here; data-props hands it the filter options and
        the JSON endpoint. The <noscript> block keeps the page useful (and gives
        crawlers real links) when JavaScript is unavailable.
    --}}
    <div data-vue="Search" data-props='@json($islandProps)'>
        {{-- Server-rendered fallback shown until the island mounts / if JS is off. --}}
        <div class="mx-auto w-full max-w-7xl px-4 py-6">
            <h1 class="text-2xl font-bold tracking-tight">Caută stomatologi</h1>
            <p class="text-muted-foreground mt-2">Se încarcă harta și filtrele…</p>

            <noscript>
                <p class="mt-4">
                    Această pagină folosește o hartă interactivă. Poți naviga și fără JavaScript
                    pornind de la un oraș:
                </p>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($cities as $city)
                        <li>
                            <x-ui.badge :href="route('city.show', $city)" variant="outline">
                                {{ $city->name }}
                            </x-ui.badge>
                        </li>
                    @endforeach
                </ul>
            </noscript>
        </div>
    </div>
</x-layouts.public>
