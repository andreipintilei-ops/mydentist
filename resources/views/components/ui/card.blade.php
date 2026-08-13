{{-- Blade mirror of resources/js/components/ui/card/Card.vue --}}
@props(['as' => 'div'])

@php
    $classes = 'bg-card text-card-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-sm';
@endphp

<{{ $as }} data-slot="card" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $as }}>
