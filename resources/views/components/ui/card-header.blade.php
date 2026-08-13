{{-- Blade mirror of resources/js/components/ui/card/CardHeader.vue --}}
@php
    // Kept inside @php so Blade never tries to parse `@container` as a directive.
    $classes = '@container/card-header grid auto-rows-min grid-rows-[auto_auto] items-start gap-1.5 px-6 has-data-[slot=card-action]:grid-cols-[1fr_auto] [.border-b]:pb-6';
@endphp

<div data-slot="card-header" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
