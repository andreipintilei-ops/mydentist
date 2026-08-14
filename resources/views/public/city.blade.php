<x-layouts.public :seo="$seo">
    <div class="mx-auto w-full max-w-6xl px-4 py-8">
        <x-breadcrumbs :items="$crumbs" />

        <header class="mt-4">
            <h1 class="text-3xl font-bold tracking-tight">Stomatologi în {{ $city->name }}</h1>
            <p class="text-muted-foreground mt-2">
                {{ $clinics->total() }}
                {{ \Illuminate\Support\Str::plural('clinică', $clinics->total()) }}
                @if ($city->county)
                    · <a href="{{ route('county.show', $city->county) }}" class="hover:underline">județul {{ $city->county->name }}</a>
                @endif
            </p>
        </header>

        {{-- Service facets that clear the thin-content threshold --}}
        @if ($services->isNotEmpty())
            <nav class="mt-6 flex flex-wrap gap-2" aria-label="Servicii în {{ $city->name }}">
                @foreach ($services as $stat)
                    <x-ui.badge :href="route('city.service', [$city, $stat->service])" variant="outline">
                        {{ $stat->service->name }} ({{ $stat->clinic_count }})
                    </x-ui.badge>
                @endforeach
            </nav>
        @endif

        {{-- Clinic list — always in the HTML, never JS-dependent --}}
        @if ($clinics->isEmpty())
            <p class="text-muted-foreground mt-10">
                Momentan nu avem clinici publicate în {{ $city->name }}.
            </p>
        @else
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($clinics as $clinic)
                    <x-clinic-card :clinic="$clinic" heading="h2" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $clinics->links() }}
            </div>
        @endif
    </div>
</x-layouts.public>
