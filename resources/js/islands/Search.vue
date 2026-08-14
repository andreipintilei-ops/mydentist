<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import ClinicList from './search/ClinicList.vue';
import ClinicMap from './search/ClinicMap.vue';
import SearchFilters from './search/SearchFilters.vue';
import type { SearchProps } from './search/types';
import { createSearchStore, provideSearch } from './search/useSearch';

const props = defineProps<SearchProps>();

const search = createSearchStore(props);
provideSearch(search);

const mobileFiltersOpen = ref(false);

// Populate the list immediately; the map refines by viewport once it loads.
onMounted(() => search.refresh());
</script>

<template>
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-6">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-bold tracking-tight">Caută stomatologi</h1>

            <!-- Mobile: filters live in a Sheet drawer; desktop shows the sidebar. -->
            <Sheet v-model:open="mobileFiltersOpen">
                <SheetTrigger as-child>
                    <Button variant="outline" size="sm" class="lg:hidden"
                        >Filtre</Button
                    >
                </SheetTrigger>
                <SheetContent side="left" class="w-80 overflow-y-auto">
                    <SheetHeader>
                        <SheetTitle>Filtre</SheetTitle>
                    </SheetHeader>
                    <div class="px-4 pb-6">
                        <SearchFilters />
                    </div>
                </SheetContent>
            </Sheet>
        </div>

        <p class="text-sm text-muted-foreground" aria-live="polite">
            <span v-if="search.loading.value">Se încarcă…</span>
            <span v-else>{{ search.count.value }} clinici găsite</span>
        </p>

        <div class="grid gap-4 lg:grid-cols-[18rem_1fr]">
            <!-- Desktop filter sidebar -->
            <aside class="hidden lg:block">
                <div class="sticky top-4">
                    <SearchFilters />
                </div>
            </aside>

            <div class="grid gap-4 lg:grid-cols-2 lg:items-stretch">
                <!-- Map: on top on mobile, right on desktop. Fixed height = no CLS. -->
                <div class="order-first h-[50vh] lg:order-last lg:h-[70vh]">
                    <ClinicMap />
                </div>

                <div class="rounded-xl border lg:h-[70vh] lg:overflow-y-auto">
                    <ClinicList />
                </div>
            </div>
        </div>
    </div>
</template>
