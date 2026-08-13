{{--
    Dev-only visual diff: Blade mirrors (left) vs real shadcn-vue components (right).
    The two columns must be indistinguishable in both light and dark mode.
    Registered only when APP_ENV=local — see routes/web.php.
--}}
<x-layouts.public title="UI parity check">
    <div class="mx-auto w-full max-w-6xl px-4 py-10">
        <h1 class="mb-2 text-2xl font-semibold tracking-tight">Verificare paritate UI</h1>
        <p class="text-muted-foreground mb-8 text-sm">
            Stânga: componente Blade. Dreapta: componente shadcn-vue.
            Trebuie să arate identic. Comută tema sistemului pentru a testa dark mode.
        </p>

        <div class="grid gap-10 md:grid-cols-2">
            {{-- ---------- Blade ---------- --}}
            <section>
                <h2 class="mb-4 text-sm font-medium uppercase tracking-wide">Blade</h2>

                <div class="space-y-6">
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach (['default', 'destructive', 'outline', 'secondary', 'ghost', 'link'] as $variant)
                            <x-ui.button :variant="$variant">{{ $variant }}</x-ui.button>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @foreach (['default', 'sm', 'lg'] as $size)
                            <x-ui.button :size="$size">size: {{ $size }}</x-ui.button>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @foreach (['default', 'secondary', 'destructive', 'outline'] as $variant)
                            <x-ui.badge :variant="$variant">{{ $variant }}</x-ui.badge>
                        @endforeach
                    </div>

                    <x-ui.separator />

                    <x-ui.input placeholder="Caută o clinică…" />

                    <x-ui.card>
                        <x-ui.card-header>
                            <x-ui.card-title>Clinica Stomatologică NovaDent</x-ui.card-title>
                            <x-ui.card-description>Strada Păcurari 12, Iași</x-ui.card-description>
                        </x-ui.card-header>
                        <x-ui.card-content>
                            <p class="text-sm">Implant dentar, ortodonție, albire dentară.</p>
                        </x-ui.card-content>
                    </x-ui.card>
                </div>
            </section>

            {{-- ---------- Vue island ---------- --}}
            <section>
                <h2 class="mb-4 text-sm font-medium uppercase tracking-wide">shadcn-vue</h2>
                <div data-vue="UiParity"></div>
            </section>
        </div>
    </div>
</x-layouts.public>
