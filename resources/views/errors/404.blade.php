<x-layouts.public title="Pagina nu a fost găsită">
    <div class="mx-auto flex max-w-xl flex-col items-center px-4 py-24 text-center">
        <p class="text-primary text-6xl font-bold tracking-tight">404</p>
        <h1 class="mt-4 text-2xl font-bold tracking-tight">Pagina nu a fost găsită</h1>
        <p class="text-muted-foreground mt-2">
            Pagina căutată nu există sau a fost mutată. Caută o clinică sau
            întoarce-te la pagina principală.
        </p>
        <div class="mt-6 flex gap-3">
            <x-ui.button :href="url('/')">Acasă</x-ui.button>
            <x-ui.button :href="url('/cauta')" variant="outline">Caută stomatologi</x-ui.button>
        </div>
    </div>
</x-layouts.public>
