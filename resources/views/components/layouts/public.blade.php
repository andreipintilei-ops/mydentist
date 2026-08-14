{{--
    Layout for the public, SEO-critical side of the site.

    Deliberately does NOT load resources/js/app.ts — no Inertia runtime reaches
    these pages. Only resources/js/public.ts, which mounts Vue islands into
    elements carrying a data-vue attribute.

    Richer <head> handling (canonical, OG, JSON-LD) arrives with <x-seo-head>.
--}}
@props([
    'title' => null,
    'description' => null,
    // Optional App\Support\Seo\SeoMeta. When present, the seo-head component
    // renders the full title/canonical/OG/JSON-LD and the simple title is skipped.
    'seo' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @if (config('directory.google_site_verification'))
            <meta name="google-site-verification" content="{{ config('directory.google_site_verification') }}">
        @endif

        @isset($seo)
            <x-seo-head :meta="$seo" />
        @else
            <title>{{ $title ? $title . ' | ' . config('app.name') : config('app.name') }}</title>

            @if ($description)
                <meta name="description" content="{{ $description }}">
            @endif
        @endisset

        {{-- Match the Inertia root's appearance handling so the two halves of the
             site never flash different themes when a user crosses between them. --}}
        <script>
            (function () {
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        <style>
            html { background-color: oklch(1 0 0); }
            html.dark { background-color: oklch(0.145 0 0); }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/public.ts'])

        {{ $head ?? '' }}
    </head>
    <body class="bg-background text-foreground font-sans antialiased">
        <div class="flex min-h-screen flex-col">
            <header class="border-b">
                <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-4">
                    <a href="{{ url('/') }}" class="text-lg font-semibold tracking-tight">
                        {{ config('app.name') }}
                    </a>

                    <nav class="flex items-center gap-2" aria-label="Navigare principală">
                        <x-ui.button href="{{ url('/cauta') }}" variant="ghost" size="sm">
                            Caută
                        </x-ui.button>
                        <x-ui.button href="{{ url('/adauga-clinica') }}" size="sm">
                            Adaugă clinica ta
                        </x-ui.button>
                    </nav>
                </div>
            </header>

            <main class="flex-1">
                @if (session('status'))
                    <div class="mx-auto w-full max-w-6xl px-4 pt-4">
                        <div class="border-primary/40 bg-primary/10 text-foreground rounded-lg border p-4 text-sm" role="status">
                            {{ session('status') }}
                        </div>
                    </div>
                @endif

                {{ $slot }}
            </main>

            <footer class="border-t">
                <div class="text-muted-foreground mx-auto w-full max-w-6xl px-4 py-8 text-sm">
                    <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
                </div>
            </footer>
        </div>
    </body>
</html>
