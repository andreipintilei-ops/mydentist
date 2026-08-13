{{-- Blade mirror of resources/js/components/ui/separator/Separator.vue --}}
@props(['orientation' => 'horizontal'])

@php
    $classes = 'bg-border shrink-0 data-[orientation=horizontal]:h-px data-[orientation=horizontal]:w-full data-[orientation=vertical]:h-full data-[orientation=vertical]:w-px';
@endphp

<div
    role="none"
    data-slot="separator"
    data-orientation="{{ $orientation }}"
    {{ $attributes->merge(['class' => $classes]) }}
></div>
