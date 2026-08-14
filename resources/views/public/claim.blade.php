<x-layouts.public :seo="$seo">
    <div class="mx-auto w-full max-w-xl px-4 py-8">
        <h1 class="text-3xl font-bold tracking-tight">Revendică clinica</h1>
        <p class="text-muted-foreground mt-2">
            Solicită administrarea listării <strong>{{ $clinic->name }}</strong>. Îți trimitem un
            email de confirmare — folosește o adresă asociată clinicii (ideal pe domeniul propriu),
            apoi echipa noastră verifică solicitarea.
        </p>

        @if ($errors->any())
            <div class="border-destructive/40 bg-destructive/10 text-foreground mt-6 rounded-lg border p-4 text-sm" role="alert">
                Te rugăm să corectezi câmpurile de mai jos.
            </div>
        @endif

        <form method="POST" action="{{ route('claim.store', $clinic) }}" class="mt-8 flex flex-col gap-5">
            @csrf

            <div class="flex flex-col gap-1.5">
                <label for="claimant_email" class="text-sm font-medium">Email de contact *</label>
                <x-ui.input
                    id="claimant_email"
                    name="claimant_email"
                    type="email"
                    :value="old('claimant_email', auth()->user()?->email)"
                    required
                />
                @error('claimant_email') <p class="text-destructive text-sm">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="message" class="text-sm font-medium">Mesaj (opțional)</label>
                <textarea
                    id="message"
                    name="message"
                    rows="4"
                    class="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                    placeholder="Spune-ne ce rol ai în clinică…"
                >{{ old('message') }}</textarea>
                @error('message') <p class="text-destructive text-sm">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3">
                <x-ui.button type="submit">Trimite solicitarea</x-ui.button>
                <a href="{{ route('clinic.show', $clinic) }}" class="text-muted-foreground text-sm hover:underline">
                    Înapoi la clinică
                </a>
            </div>
        </form>
    </div>
</x-layouts.public>
