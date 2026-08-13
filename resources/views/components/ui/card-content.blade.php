{{-- Blade mirror of resources/js/components/ui/card/CardContent.vue --}}
@php
    $classes = 'px-6';
@endphp

<div data-slot="card-content" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
