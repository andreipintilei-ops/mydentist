<script setup lang="ts">
import type { Map as LeafletMap } from 'leaflet';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { loadLeaflet } from './lib/leaflet';

const props = defineProps<{
    lat: number;
    lng: number;
    name: string;
    address?: string;
}>();

const container = ref<HTMLDivElement | null>(null);
const loaded = ref(false);

// Desktop auto-loads on scroll; mobile waits for a tap (below the fold, heavy).
const canAutoLoad =
    typeof window !== 'undefined' &&
    window.matchMedia('(min-width: 768px)').matches;

let map: LeafletMap | null = null;

function escapeHtml(value: string): string {
    const entities: Record<string, string> = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    };

    return value.replace(/[&<>"']/g, (ch) => entities[ch] ?? ch);
}

async function load(): Promise<void> {
    if (loaded.value || !container.value) {
        return;
    }

    loaded.value = true;

    const L = await loadLeaflet();

    // scrollWheelZoom off so the page still scrolls past the map on the profile.
    map = L.map(container.value, { scrollWheelZoom: false }).setView(
        [props.lat, props.lng],
        15,
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    const directions = `https://www.google.com/maps/dir/?api=1&destination=${props.lat},${props.lng}`;
    const popup =
        (props.address
            ? `<strong>${escapeHtml(props.name)}</strong><br>${escapeHtml(props.address)}`
            : `<strong>${escapeHtml(props.name)}</strong>`) +
        `<br><a href="${directions}" target="_blank" rel="noopener nofollow" class="underline">Direcții</a>`;

    L.marker([props.lat, props.lng]).addTo(map).bindPopup(popup);
}

onMounted(() => {
    if (!canAutoLoad) {
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                observer.disconnect();
                void load();
            }
        },
        { rootMargin: '200px' },
    );

    if (container.value) {
        observer.observe(container.value);
    }
});

onBeforeUnmount(() => {
    map?.remove();
    map = null;
});
</script>

<template>
    <!-- Fixed aspect ratio reserves space so lazy Leaflet never shifts layout. -->
    <div class="relative aspect-video w-full overflow-hidden rounded-xl border">
        <div
            ref="container"
            class="h-full w-full"
            role="application"
            aria-label="Hartă clinică"
        ></div>

        <button
            v-if="!loaded && !canAutoLoad"
            type="button"
            class="absolute inset-0 flex items-center justify-center bg-muted text-sm text-muted-foreground"
            @click="load"
        >
            Apasă pentru a încărca harta
        </button>
    </div>
</template>
