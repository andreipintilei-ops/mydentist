<x-layouts.public :seo="$seo">
    <div class="mx-auto w-full max-w-6xl px-4 py-8">
        <x-breadcrumbs :items="$crumbs" />

        <div class="mt-4 grid gap-8 lg:grid-cols-3">
            {{-- Main column --}}
            <div class="lg:col-span-2">
                <header class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">{{ $clinic->name }}</h1>
                        <p class="text-muted-foreground mt-2">
                            {{ $clinic->address }},
                            <a href="{{ route('city.show', $clinic->city) }}" class="hover:underline">{{ $clinic->city->name }}</a>
                        </p>
                    </div>
                    @if ($clinic->cnas_contract)
                        <x-ui.badge variant="secondary">Contract CNAS</x-ui.badge>
                    @endif
                </header>

                @if ($clinic->rating_count > 0)
                    <div class="mt-4">
                        <x-star-rating :rating="$clinic->rating_avg" :count="$clinic->rating_count" />
                    </div>
                @endif

                @if ($clinic->description)
                    <div class="mt-6 leading-relaxed">
                        <p>{{ $clinic->description }}</p>
                    </div>
                @endif

                {{-- Services --}}
                @if ($clinic->services->isNotEmpty())
                    <section class="mt-8">
                        <h2 class="text-xl font-semibold tracking-tight">Servicii</h2>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($clinic->services as $service)
                                <x-ui.badge :href="route('city.service', [$clinic->city, $service])" variant="outline">
                                    {{ $service->name }}
                                </x-ui.badge>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Photos --}}
                @if ($clinic->photos->isNotEmpty())
                    <section class="mt-8">
                        <h2 class="text-xl font-semibold tracking-tight">Galerie</h2>
                        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($clinic->photos as $photo)
                                <img
                                    src="{{ $photo->url }}"
                                    alt="{{ $photo->alt ?? $clinic->name }}"
                                    @if ($photo->width) width="{{ $photo->width }}" @endif
                                    @if ($photo->height) height="{{ $photo->height }}" @endif
                                    loading="lazy"
                                    class="aspect-video w-full rounded-lg border object-cover"
                                >
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Reviews --}}
                @if ($clinic->reviews->isNotEmpty())
                    <section class="mt-8">
                        <h2 class="text-xl font-semibold tracking-tight">Recenzii</h2>
                        <div class="mt-4 flex flex-col gap-4">
                            @foreach ($clinic->reviews as $review)
                                <x-ui.card class="py-4">
                                    <x-ui.card-content class="flex flex-col gap-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-medium">{{ $review->author_name }}</span>
                                            <span class="text-sm text-amber-500" aria-label="{{ $review->rating }} din 5">
                                                {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}
                                            </span>
                                        </div>
                                        @if ($review->body)
                                            <p class="text-muted-foreground text-sm">{{ $review->body }}</p>
                                        @endif
                                    </x-ui.card-content>
                                </x-ui.card>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            {{-- Sidebar --}}
            <aside class="flex flex-col gap-6">
                {{-- Contact --}}
                <x-ui.card>
                    <x-ui.card-header>
                        <x-ui.card-title as="h2" class="text-base">Contact</x-ui.card-title>
                    </x-ui.card-header>
                    <x-ui.card-content class="flex flex-col gap-2 text-sm">
                        @if ($clinic->phone)
                            <a href="tel:{{ $clinic->phone }}" class="hover:underline">{{ $clinic->phone }}</a>
                        @endif
                        @if ($clinic->email)
                            <a href="mailto:{{ $clinic->email }}" class="hover:underline">{{ $clinic->email }}</a>
                        @endif
                        @if ($clinic->website)
                            <a href="{{ $clinic->website }}" rel="nofollow noopener" target="_blank" class="hover:underline break-all">
                                {{ preg_replace('#^https?://#', '', $clinic->website) }}
                            </a>
                        @endif

                        @unless ($clinic->claimed_by)
                            <a href="{{ route('claim.create', $clinic) }}" class="text-primary mt-1 hover:underline">
                                Ești proprietarul? Revendică această clinică
                            </a>
                        @endunless
                    </x-ui.card-content>
                </x-ui.card>

                {{-- Locator map. The island lazy-loads Leaflet; the fixed aspect
                     ratio reserves space so it mounts without layout shift. Only
                     rendered once the clinic is geocoded (0/0 = not yet located). --}}
                @if ($mapProps)
                    <div class="flex flex-col gap-2">
                        <div data-vue="ClinicLocationMap" data-props='@json($mapProps)'>
                            <div class="bg-muted text-muted-foreground flex aspect-video items-center justify-center rounded-xl border text-sm">
                                Se încarcă harta…
                            </div>
                        </div>

                        <x-ui.button
                            :href="$clinic->directions_url"
                            variant="outline"
                            class="w-full"
                            target="_blank"
                            rel="noopener nofollow"
                        >
                            Direcții
                        </x-ui.button>
                    </div>
                @endif

                {{-- Opening hours --}}
                @if ($clinic->openingHours->isNotEmpty())
                    <x-ui.card>
                        <x-ui.card-header>
                            <x-ui.card-title as="h2" class="text-base">Program</x-ui.card-title>
                        </x-ui.card-header>
                        <x-ui.card-content>
                            <x-opening-hours :clinic="$clinic" />
                        </x-ui.card-content>
                    </x-ui.card>
                @endif
            </aside>
        </div>
    </div>
</x-layouts.public>
