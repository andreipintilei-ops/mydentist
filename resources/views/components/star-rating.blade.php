{{--
    Visible star rating with an accessible label. Purely presentational — the
    machine-readable rating lives in the clinic's JSON-LD aggregateRating, so we
    do not duplicate microdata here.
--}}
@props([
    'rating' => 0,
    'count' => 0,
])

@php
    $rounded = (int) round((float) $rating);
@endphp

<div class="flex items-center gap-1.5" aria-label="Rating {{ number_format((float) $rating, 1) }} din 5 ({{ $count }} recenzii)">
    <span class="text-sm tracking-tight text-amber-500" aria-hidden="true">
        {{ str_repeat('★', $rounded) }}{{ str_repeat('☆', 5 - $rounded) }}
    </span>
    <span class="text-muted-foreground text-sm">
        {{ number_format((float) $rating, 1) }} ({{ $count }})
    </span>
</div>
