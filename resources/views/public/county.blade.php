<x-layouts.public :seo="$seo">
    <div class="mx-auto w-full max-w-6xl px-4 py-8">
        <x-breadcrumbs :items="$crumbs" />

        <header class="mt-4">
            <h1 class="text-3xl font-bold tracking-tight">
                Stomatologi în județul {{ $county->name }}
            </h1>
            <p class="text-muted-foreground mt-2">
                {{ $totalClinics }} {{ \Illuminate\Support\Str::plural('clinică', $totalClinics) }}
                în {{ $cities->count() }} {{ \Illuminate\Support\Str::plural('oraș', $cities->count()) }}
            </p>
        </header>

        @if ($cities->isEmpty())
            <p class="text-muted-foreground mt-10">
                Momentan nu avem clinici publicate în județul {{ $county->name }}.
            </p>
        @else
            <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($cities as $city)
                    <a href="{{ route('city.show', $city) }}" class="group">
                        <x-ui.card class="h-full py-4 transition-colors group-hover:border-primary">
                            <x-ui.card-content class="flex items-center justify-between">
                                <span class="font-medium">{{ $city->name }}</span>
                                <span class="text-muted-foreground text-sm">
                                    {{ $city->clinics_count }}
                                    {{ \Illuminate\Support\Str::plural('clinică', $city->clinics_count) }}
                                </span>
                            </x-ui.card-content>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.public>
