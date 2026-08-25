<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import ClinicList from './search/ClinicList.vue';
import ClinicMap from './search/ClinicMap.vue';
import ResultsSheet from './search/ResultsSheet.vue';
import SearchTopBar from './search/SearchTopBar.vue';
import type { SearchProps } from './search/types';
import { createSearchStore, provideSearch } from './search/useSearch';

const props = defineProps<SearchProps>();

const search = createSearchStore(props);
provideSearch(search);

/**
 * The floating results panel (22rem wide, 1rem from the edge) hides the map's
 * left edge on large screens. The map needs the figure to recentre against.
 */
const PANEL_OFFSET = 368;

const isDesktop = ref(false);
let query: MediaQueryList | null = null;

function syncBreakpoint(event: MediaQueryList | MediaQueryListEvent): void {
    isDesktop.value = event.matches;
}

const centerOffsetX = computed(() => (isDesktop.value ? PANEL_OFFSET : 0));

onMounted(() => {
    query = window.matchMedia('(min-width: 1024px)');
    syncBreakpoint(query);
    query.addEventListener('change', syncBreakpoint);

    // Populate the list immediately; the map refines by viewport once it loads.
    search.refresh();
});

onBeforeUnmount(() => query?.removeEventListener('change', syncBreakpoint));
</script>

<template>
    <div class="flex h-full w-full flex-col">
        <SearchTopBar />

        <!-- The map fills this box; every panel floats above it. Nothing here
             resizes the map container, so Leaflet never needs invalidateSize(). -->
        <div class="relative min-h-0 flex-1">
            <ClinicMap :center-offset-x="centerOffsetX" />

            <!-- Desktop: results float over the map's left edge. -->
            <aside
                class="absolute inset-y-4 left-4 z-10 hidden w-[22rem] flex-col overflow-hidden rounded-xl border bg-background shadow-lg lg:flex"
                aria-label="Rezultate"
            >
                <div class="shrink-0 border-b px-4 py-3">
                    <p class="text-sm font-medium" aria-live="polite">
                        <span v-if="search.loading.value">Se încarcă…</span>
                        <span v-else-if="search.capped.value"
                            >{{ search.count.value }}+ clinici</span
                        >
                        <span v-else
                            >{{ search.count.value }} clinici găsite</span
                        >
                    </p>
                    <p
                        v-if="search.capped.value"
                        class="mt-0.5 text-xs text-muted-foreground"
                    >
                        Sunt mai multe decât putem afișa — micșorează zona sau
                        adaugă filtre.
                    </p>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto">
                    <ClinicList />
                </div>
            </aside>

            <!-- Mobile: the same results as a drag-to-expand bottom sheet. -->
            <ResultsSheet class="lg:hidden" />
        </div>
    </div>
</template>
