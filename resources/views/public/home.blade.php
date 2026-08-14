<x-layouts.public :seo="$seo">
    {{-- Hero --}}
    <section class="border-b bg-muted/30">
        <div class="mx-auto w-full max-w-6xl px-4 py-16 md:py-24">
            <h1 class="max-w-3xl text-3xl font-bold tracking-tight text-balance md:text-5xl">
                Găsește un stomatolog de încredere în România
            </h1>
            <p class="text-muted-foreground mt-4 max-w-2xl text-lg">
                Clinici și cabinete stomatologice, pe orașe și specializări. Compară servicii,
                program și recenzii, apoi programează-te.
            </p>

            <div class="mt-8 flex flex-wrap gap-2">
                @foreach ($cities->take(6) as $city)
                    <x-ui.button :href="route('city.show', $city)" variant="outline" size="sm">
                        {{ $city->name }}
                    </x-ui.button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Services --}}
    <section class="mx-auto w-full max-w-6xl px-4 py-12">
        <h2 class="text-2xl font-semibold tracking-tight">Specializări</h2>
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($services as $service)
                <a href="{{ route('service.show', $service) }}" class="group">
                    <x-ui.card class="h-full py-4 transition-colors group-hover:border-primary">
                        <x-ui.card-content>
                            <span class="font-medium">{{ $service->name }}</span>
                        </x-ui.card-content>
                    </x-ui.card>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Popular cities --}}
    @if ($cities->isNotEmpty())
        <section class="mx-auto w-full max-w-6xl px-4 py-12">
            <h2 class="text-2xl font-semibold tracking-tight">Orașe populare</h2>
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($cities as $city)
                    <a href="{{ route('city.show', $city) }}" class="group">
                        <x-ui.card class="h-full py-4 transition-colors group-hover:border-primary">
                            <x-ui.card-content class="flex items-center justify-between">
                                <span class="font-medium">{{ $city->name }}</span>
                                <span class="text-muted-foreground text-sm">{{ $city->clinics_count }}</span>
                            </x-ui.card-content>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Featured clinics --}}
    @if ($featured->isNotEmpty())
        <section class="mx-auto w-full max-w-6xl px-4 py-12">
            <h2 class="text-2xl font-semibold tracking-tight">Clinici bine cotate</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $clinic)
                    <x-clinic-card :clinic="$clinic" heading="h3" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.public>
