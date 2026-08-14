{{--
    A single clinic in a listing. The `clinic-card` class is load-bearing: the
    SEO smoke test greps the RAW HTML for it to prove listings are server-
    rendered (no JS needed to see clinics). Keep it.

    `heading` sets the card title's element so the document outline stays correct
    — clinic names are <h2> under a city page's <h1>.
--}}
@props([
    'clinic',
    'heading' => 'h2',
])

<x-ui.card class="clinic-card h-full">
    <x-ui.card-header>
        <div class="flex items-start justify-between gap-3">
            <x-ui.card-title :as="$heading" class="text-base">
                <a href="{{ route('clinic.show', $clinic) }}" class="hover:underline">
                    {{ $clinic->name }}
                </a>
            </x-ui.card-title>

            @if ($clinic->cnas_contract)
                <x-ui.badge variant="secondary" class="shrink-0">CNAS</x-ui.badge>
            @endif
        </div>

        <x-ui.card-description>
            {{ $clinic->address }}, {{ $clinic->city?->name }}
        </x-ui.card-description>
    </x-ui.card-header>

    <x-ui.card-content class="flex flex-col gap-3">
        @if ($clinic->rating_count > 0)
            <x-star-rating :rating="$clinic->rating_avg" :count="$clinic->rating_count" />
        @endif

        @if ($clinic->services->isNotEmpty())
            <div class="flex flex-wrap gap-1.5">
                @foreach ($clinic->services->take(4) as $service)
                    <x-ui.badge variant="outline">{{ $service->name }}</x-ui.badge>
                @endforeach
                @if ($clinic->services->count() > 4)
                    <x-ui.badge variant="outline">+{{ $clinic->services->count() - 4 }}</x-ui.badge>
                @endif
            </div>
        @endif

        @isset($clinic->distance_m)
            <p class="text-muted-foreground text-sm">
                la {{ number_format($clinic->distance_m / 1000, 1) }} km
            </p>
        @endisset
    </x-ui.card-content>
</x-ui.card>
