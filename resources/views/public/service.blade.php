<x-layouts.public :seo="$seo">
    <div class="mx-auto w-full max-w-6xl px-4 py-8">
        <x-breadcrumbs :items="$crumbs" />

        <header class="mt-4">
            <h1 class="text-3xl font-bold tracking-tight">{{ $service->name }}</h1>
            @if ($service->description)
                <p class="text-muted-foreground mt-2 max-w-3xl">{{ $service->description }}</p>
            @endif
        </header>

        <section class="mt-8">
            <h2 class="text-xl font-semibold tracking-tight">Alege orașul</h2>

            @if ($cities->isEmpty())
                <p class="text-muted-foreground mt-4">
                    Momentan nu avem suficiente clinici publicate pentru {{ $service->name }}.
                </p>
            @else
                <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($cities as $stat)
                        <a href="{{ route('city.service', [$stat->city, $service]) }}" class="group">
                            <x-ui.card class="h-full py-4 transition-colors group-hover:border-primary">
                                <x-ui.card-content class="flex items-center justify-between">
                                    <span class="font-medium">{{ $stat->city->name }}</span>
                                    <span class="text-muted-foreground text-sm">{{ $stat->clinic_count }}</span>
                                </x-ui.card-content>
                            </x-ui.card>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-layouts.public>
