{{--
    Visible breadcrumb trail. Feed it the SAME array used to build the
    BreadcrumbList JSON-LD so the two never disagree.

    $items: array<int, array{name: string, url?: string|null}>
    The last item is treated as the current page (no link).
--}}
@props(['items' => []])

<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'text-muted-foreground text-sm']) }}>
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach ($items as $i => $item)
            <li class="flex items-center gap-1.5">
                @if (! $loop->last && ! empty($item['url']))
                    <a href="{{ $item['url'] }}" class="hover:text-foreground hover:underline">
                        {{ $item['name'] }}
                    </a>
                    <span aria-hidden="true">/</span>
                @else
                    <span class="text-foreground" aria-current="page">{{ $item['name'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
