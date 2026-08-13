{{-- Blade mirror of resources/js/components/ui/card/CardDescription.vue --}}
@php
    $classes = 'text-muted-foreground text-sm';
@endphp

<p data-slot="card-description" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</p>
