<x-layouts.public :seo="$seo">
    <div class="mx-auto w-full max-w-6xl px-4 py-8">
        <x-breadcrumbs :items="$crumbs" />

        <header class="mt-4">
            <h1 class="text-3xl font-bold tracking-tight">
                {{ $service->name }} în {{ $city->name }}
            </h1>
            @if ($service->description)
                <p class="text-muted-foreground mt-2 max-w-3xl">{{ $service->description }}</p>
            @endif
            <p class="text-muted-foreground mt-2 text-sm">
                {{ $clinics->total() }}
                {{ \Illuminate\Support\Str::plural('clinică', $clinics->total()) }}
                oferă acest serviciu în {{ $city->name }}.
            </p>
        </header>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($clinics as $clinic)
                <x-clinic-card :clinic="$clinic" heading="h2" />
            @endforeach
        </div>

        <div class="mt-8">
            {{ $clinics->links() }}
        </div>

        <p class="text-muted-foreground mt-10 text-sm">
            <a href="{{ route('city.show', $city) }}" class="hover:underline">
                ← Toți stomatologii din {{ $city->name }}
            </a>
        </p>
    </div>
</x-layouts.public>
