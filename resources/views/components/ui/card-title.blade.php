{{--
    Blade mirror of resources/js/components/ui/card/CardTitle.vue

    `as` is exposed because on public listing pages the heading level carries
    SEO meaning — a clinic card inside an <h1> city page should be an <h2>,
    not a hardcoded <h3>.
--}}
@props(['as' => 'h3'])

@php
    $classes = 'leading-none font-semibold';
@endphp

<{{ $as }} data-slot="card-title" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $as }}>
