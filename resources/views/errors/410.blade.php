<x-layouts.public title="Conținut indisponibil">
    <div class="mx-auto flex max-w-xl flex-col items-center px-4 py-24 text-center">
        <p class="text-primary text-6xl font-bold tracking-tight">410</p>
        <h1 class="mt-4 text-2xl font-bold tracking-tight">Această pagină nu mai există</h1>
        <p class="text-muted-foreground mt-2">
            Clinica sau pagina a fost eliminată definitiv. Poți căuta o alternativă
            în apropiere.
        </p>
        <div class="mt-6 flex gap-3">
            <x-ui.button :href="url('/')">Acasă</x-ui.button>
            <x-ui.button :href="url('/cauta')" variant="outline">Caută stomatologi</x-ui.button>
        </div>
    </div>
</x-layouts.public>
