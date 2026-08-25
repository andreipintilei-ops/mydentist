<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import CityCombobox from './CityCombobox.vue';
import SearchFilters from './SearchFilters.vue';
import { useSearch } from './useSearch';

const search = useSearch();

const filtersOpen = ref(false);
</script>

<template>
    <div class="shrink-0 border-b bg-background">
        <div class="flex items-center gap-2 px-3 py-2.5 sm:px-4">
            <h1 class="sr-only">Caută stomatologi</h1>

            <!-- min-w-0 so the input yields space to the Filtre button instead
                 of pushing it off a narrow screen — the page cannot scroll
                 sideways to reach it. -->
            <Input
                v-model="search.filters.q"
                type="search"
                class="min-w-0 flex-1 sm:max-w-xs"
                placeholder="Nume clinică sau adresă…"
                aria-label="Caută după nume sau adresă"
            />

            <!-- Below sm the bar only has room for the search box; the city
                 picker still lives in the filter panel, so nothing is lost. -->
            <div class="hidden w-56 sm:block">
                <CityCombobox
                    :cities="search.cities"
                    @select="search.selectCity($event)"
                />
            </div>

            <Sheet v-model:open="filtersOpen">
                <SheetTrigger as-child>
                    <Button variant="outline" class="shrink-0">
                        Filtre
                        <span
                            v-if="search.activeFilterCount.value > 0"
                            class="ml-1.5 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1 text-xs text-primary-foreground"
                            >{{ search.activeFilterCount.value }}</span
                        >
                    </Button>
                </SheetTrigger>
                <!-- Opens from the right so it never covers the results panel. -->
                <SheetContent side="right" class="w-80 overflow-y-auto">
                    <SheetHeader>
                        <SheetTitle>Filtre</SheetTitle>
                    </SheetHeader>
                    <div class="px-4 pb-6">
                        <SearchFilters />
                    </div>
                </SheetContent>
            </Sheet>

            <!-- Hidden on mobile: the bottom sheet already shows the count, and
                 the bar has no room to spare there. -->
            <p
                class="ml-auto hidden shrink-0 text-sm text-muted-foreground sm:block"
                aria-live="polite"
            >
                <span v-if="search.loading.value">Se încarcă…</span>
                <span v-else-if="search.capped.value"
                    >{{ search.count.value }}+ clinici</span
                >
                <span v-else>{{ search.count.value }} clinici</span>
            </p>
        </div>

        <!-- Active filters stay visible here, so nobody wonders why the map is
             empty while "Deschis acum" is quietly on inside a closed panel. -->
        <div
            v-if="search.chips.value.length"
            class="flex flex-wrap items-center gap-1.5 px-3 pb-2.5 sm:px-4"
        >
            <button
                v-for="chip in search.chips.value"
                :key="chip.key"
                type="button"
                class="inline-flex items-center gap-1 rounded-full border bg-secondary px-2.5 py-1 text-xs text-secondary-foreground transition-colors hover:bg-secondary/70"
                @click="chip.clear()"
            >
                {{ chip.label }}
                <span aria-hidden="true">✕</span>
                <span class="sr-only">Elimină filtrul {{ chip.label }}</span>
            </button>

            <Button
                variant="ghost"
                size="sm"
                class="h-7 px-2 text-xs"
                @click="search.resetFilters()"
            >
                Resetează tot
            </Button>
        </div>
    </div>
</template>
