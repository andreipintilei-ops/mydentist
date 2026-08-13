{{-- Blade mirror of resources/js/components/ui/card/CardFooter.vue --}}
@php
    $classes = 'flex items-center px-6 [.border-t]:pt-6';
@endphp

<div data-slot="card-footer" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
