<script setup lang="ts">
import type * as Leaflet from 'leaflet';
import type {
    LatLngExpression,
    Map as LeafletMap,
    MarkerClusterGroup,
} from 'leaflet';
// Type-only: pulls the markercluster augmentation of 'leaflet' into the type
// program (adds L.markerClusterGroup) without an eager runtime import.
import type {} from 'leaflet.markercluster';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { loadLeaflet, loadMarkerCluster } from '../lib/leaflet';
import { useSearch } from './useSearch';

const search = useSearch();

const container = ref<HTMLDivElement | null>(null);
const loaded = ref(false);

// Desktop auto-loads on scroll; small screens wait for a tap (the map is heavy
// and often below the fold on mobile) — the "click-to-load" the plan calls for.
const canAutoLoad =
    typeof window !== 'undefined' &&
    window.matchMedia('(min-width: 768px)').matches;

let L: typeof Leaflet | null = null;
let map: LeafletMap | null = null;
let cluster: MarkerClusterGroup | null = null;

async function load(): Promise<void> {
    if (loaded.value || !container.value) {
        return;
    }

    loaded.value = true;

    L = await loadLeaflet();
    await loadMarkerCluster();

    map = L.map(container.value).setView(
        search.center.value,
        search.zoom.value,
    );
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    cluster = L.markerClusterGroup();
    map.addLayer(cluster);

    map.on('moveend', emitBounds);
    emitBounds();
    renderMarkers();
}

function emitBounds(): void {
    if (!map) {
        return;
    }

    const b = map.getBounds();
    search.setBounds({
        min_lat: b.getSouth(),
        max_lat: b.getNorth(),
        min_lng: b.getWest(),
        max_lng: b.getEast(),
    });
}

function escapeHtml(value: string): string {
    const map: Record<string, string> = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    };

    return value.replace(/[&<>"']/g, (ch) => map[ch] ?? ch);
}

function renderMarkers(): void {
    if (!L || !cluster) {
        return;
    }

    cluster.clearLayers();

    for (const clinic of search.clinics.value) {
        const marker = L.marker([clinic.lat, clinic.lng]);
        const directions = `https://www.google.com/maps/dir/?api=1&destination=${clinic.lat},${clinic.lng}`;
        marker.bindPopup(
            `<a href="${clinic.url}" class="font-medium underline">${escapeHtml(clinic.name)}</a>` +
                `<br><span>${escapeHtml(clinic.address)}</span>` +
                `<br><a href="${directions}" target="_blank" rel="noopener nofollow" class="underline">Direcții</a>`,
        );
        cluster.addLayer(marker);
    }
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

// Re-render pins whenever the result set changes.
watch(search.clinics, () => renderMarkers());

// Recenter when a city is chosen (setView fires moveend -> refetch by bounds).
watch([search.center, search.zoom], () => {
    if (map) {
        map.setView(search.center.value as LatLngExpression, search.zoom.value);
    }
});

onBeforeUnmount(() => {
    map?.remove();
    map = null;
    cluster = null;
});
</script>

<template>
    <!-- Fixed-size box reserves space so lazy Leaflet never shifts layout (CLS). -->
    <div
        class="relative h-full min-h-[320px] w-full overflow-hidden rounded-xl border"
    >
        <div
            ref="container"
            class="h-full w-full"
            role="application"
            aria-label="Hartă clinici"
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
