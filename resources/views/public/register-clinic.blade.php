<x-layouts.public :seo="$seo">
    <div class="mx-auto w-full max-w-2xl px-4 py-8">
        <h1 class="text-3xl font-bold tracking-tight">Adaugă clinica ta</h1>
        <p class="text-muted-foreground mt-2">
            Completează datele clinicii. Le verificăm înainte de publicare, așa că
            listarea nu apare imediat.
        </p>

        @if (session('status'))
            <div class="border-primary/40 bg-primary/10 text-foreground mt-6 rounded-lg border p-4 text-sm" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="border-destructive/40 bg-destructive/10 text-foreground mt-6 rounded-lg border p-4 text-sm" role="alert">
                Te rugăm să corectezi câmpurile marcate mai jos.
            </div>
        @endif

        <form method="POST" action="{{ route('clinic.register.store') }}" class="mt-8 flex flex-col gap-5">
            @csrf

            @php
                $labelClass = 'text-sm font-medium';
                $errorClass = 'text-destructive text-sm';
                $controlClass = 'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]';
            @endphp

            <div class="flex flex-col gap-1.5">
                <label for="name" class="{{ $labelClass }}">Numele clinicii *</label>
                <x-ui.input id="name" name="name" :value="old('name')" required />
                @error('name') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="city_id" class="{{ $labelClass }}">Oraș *</label>
                <select id="city_id" name="city_id" required class="{{ $controlClass }}">
                    <option value="">Alege orașul…</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}" @selected(old('city_id') == $city->id)>
                            {{ $city->name }}@if ($city->county && $city->county->name !== $city->name) ({{ $city->county->name }})@endif
                        </option>
                    @endforeach
                </select>
                @error('city_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="address" class="{{ $labelClass }}">Adresă *</label>
                <x-ui.input id="address" name="address" :value="old('address')" placeholder="Strada, număr, oraș" required />
                @error('address') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="phone" class="{{ $labelClass }}">Telefon *</label>
                    <x-ui.input id="phone" name="phone" :value="old('phone')" required />
                    @error('phone') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="email" class="{{ $labelClass }}">Email *</label>
                    <x-ui.input id="email" name="email" type="email" :value="old('email')" required />
                    @error('email') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="website" class="{{ $labelClass }}">Site web</label>
                <x-ui.input id="website" name="website" type="url" :value="old('website')" placeholder="https://" />
                @error('website') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="description" class="{{ $labelClass }}">Descriere</label>
                <textarea id="description" name="description" rows="5" class="{{ $controlClass }}" placeholder="Servicii, dotări, echipă…">{{ old('description') }}</textarea>
                @error('description') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>

            <fieldset class="flex flex-col gap-2">
                <legend class="{{ $labelClass }} mb-1">Servicii oferite</legend>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($services as $service)
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                name="services[]"
                                value="{{ $service->id }}"
                                @checked(collect(old('services'))->contains($service->id))
                                class="border-input size-4 rounded"
                            >
                            {{ $service->name }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="cnas_contract" value="1" @checked(old('cnas_contract')) class="border-input size-4 rounded">
                Am contract cu CNAS
            </label>

            <div class="pt-2">
                <x-ui.button type="submit">Trimite clinica</x-ui.button>
            </div>
        </form>
    </div>
</x-layouts.public>
