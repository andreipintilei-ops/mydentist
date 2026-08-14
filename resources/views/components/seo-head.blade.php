{{--
    Renders every <head> SEO tag from a single App\Support\Seo\SeoMeta value
    object: title, description, canonical, robots, Open Graph, and one
    <script type="application/ld+json"> per JSON-LD graph.

    The public layout renders this automatically when a page passes an :seo prop,
    so pages never place it by hand.
--}}
@props(['meta'])

@php
    /** @var \App\Support\Seo\SeoMeta $meta */
    $suffix = config('app.name');
    $fullTitle = $meta->title === $suffix ? $suffix : $meta->title.' | '.$suffix;
    $canonical = $meta->canonical ?? url()->current();
@endphp

<title>{{ $fullTitle }}</title>

@if ($meta->description)
    <meta name="description" content="{{ $meta->description }}">
@endif

<meta name="robots" content="{{ $meta->robots }}">

{{-- Self-referencing canonical on every indexable page. Paginated listing pages
     pass a canonical that includes ?pagina=N, i.e. they are self-canonical and
     never collapse to page 1. --}}
<link rel="canonical" href="{{ $canonical }}">

{{-- Open Graph --}}
<meta property="og:site_name" content="{{ $suffix }}">
<meta property="og:type" content="{{ $meta->ogType }}">
<meta property="og:title" content="{{ $fullTitle }}">
@if ($meta->description)
    <meta property="og:description" content="{{ $meta->description }}">
@endif
<meta property="og:url" content="{{ $canonical }}">
@if ($meta->ogImage)
    <meta property="og:image" content="{{ $meta->ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
@else
    <meta name="twitter:card" content="summary">
@endif

{{-- JSON-LD graphs. JSON_UNESCAPED_SLASHES/UNICODE keep URLs and Romanian
     diacritics readable; JSON_UNESCAPED_UNICODE is safe inside a <script>. --}}
@foreach ($meta->jsonLd as $graph)
    <script type="application/ld+json">
        {!! json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endforeach
