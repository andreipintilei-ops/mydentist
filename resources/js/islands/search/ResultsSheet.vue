<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import ClinicList from './ClinicList.vue';
import { useSearch } from './useSearch';

const search = useSearch();

/** Collapsed height: just the grabber and the result count. */
const PEEK = 96;

const root = ref<HTMLElement | null>(null);
const height = ref(PEEK);
const dragging = ref(false);

/**
 * Snap points, measured against the map area rather than the window so the
 * sheet never runs under the filter bar.
 */
function areaHeight(): number {
    return root.value?.parentElement?.clientHeight ?? window.innerHeight;
}

function snaps(): number[] {
    const h = areaHeight();

    return [PEEK, Math.round(h * 0.45), Math.round(h * 0.88)];
}

function nearest(value: number): number {
    return snaps().reduce((best, snap) =>
        Math.abs(snap - value) < Math.abs(best - value) ? snap : best,
    );
}

const expanded = computed(() => height.value > PEEK + 8);

let startY = 0;
let startHeight = 0;
let travelled = 0;
// A drag ends with a click event too; swallow that one so it does not also cycle.
let suppressClick = false;

function onPointerDown(event: PointerEvent): void {
    dragging.value = true;
    startY = event.clientY;
    startHeight = height.value;
    travelled = 0;
    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
}

function onPointerMove(event: PointerEvent): void {
    if (!dragging.value) {
        return;
    }

    const delta = startY - event.clientY;
    travelled = Math.max(travelled, Math.abs(delta));
    height.value = Math.min(
        Math.max(startHeight + delta, PEEK),
        Math.round(areaHeight() * 0.88),
    );
}

function onPointerUp(event: PointerEvent): void {
    if (!dragging.value) {
        return;
    }

    dragging.value = false;
    (event.currentTarget as HTMLElement).releasePointerCapture(event.pointerId);

    if (travelled >= 6) {
        height.value = nearest(height.value);
        suppressClick = true;
    }
}

/** Tap (or keyboard activation) cycles peek -> half -> full -> peek. */
function onToggle(): void {
    if (suppressClick) {
        suppressClick = false;

        return;
    }

    const points = snaps();
    const index = points.indexOf(nearest(height.value));
    height.value = points[(index + 1) % points.length] ?? PEEK;
}

function clampToArea(): void {
    height.value = Math.min(height.value, Math.round(areaHeight() * 0.88));
}

onMounted(() => window.addEventListener('resize', clampToArea));
onBeforeUnmount(() => window.removeEventListener('resize', clampToArea));
</script>

<template>
    <section
        ref="root"
        class="absolute inset-x-0 bottom-0 z-20 flex flex-col rounded-t-2xl border-t bg-background shadow-[0_-8px_30px_rgb(0_0_0/0.12)]"
        :class="dragging ? '' : 'transition-[height] duration-200 ease-out'"
        :style="{ height: `${height}px` }"
        aria-label="Rezultate"
    >
        <button
            type="button"
            class="flex w-full shrink-0 cursor-grab touch-none flex-col items-center gap-1.5 px-4 pt-2 pb-1 active:cursor-grabbing"
            :aria-expanded="expanded"
            @pointerdown="onPointerDown"
            @pointermove="onPointerMove"
            @pointerup="onPointerUp"
            @pointercancel="onPointerUp"
            @click="onToggle"
        >
            <span
                class="h-1.5 w-10 rounded-full bg-muted-foreground/30"
                aria-hidden="true"
            ></span>
            <span class="text-sm font-medium">
                <template v-if="search.loading.value">Se încarcă…</template>
                <template v-else-if="search.capped.value"
                    >{{ search.count.value }}+ clinici</template
                >
                <template v-else>{{ search.count.value }} clinici</template>
            </span>
        </button>

        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
            <ClinicList />
        </div>
    </section>
</template>
