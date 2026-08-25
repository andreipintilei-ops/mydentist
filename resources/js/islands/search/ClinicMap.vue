<script setup lang="ts">
import type * as Leaflet from 'leaflet';
import type { Map as LeafletMap, Marker, MarkerClusterGroup } from 'leaflet';
// Type-only: pulls the markercluster augmentation of 'leaflet' into the type
// program (adds L.markerClusterGroup) without an eager runtime import.
import type {} from 'leaflet.markercluster';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { loadLeaflet, loadMarkerCluster } from '../lib/leaflet';
import { useSearch } from './useSearch';

const props = withDefaults(
    defineProps<{
        /**
         * Width in px of the chrome covering the map's left edge (the results
         * panel). Recentring compensates for it, otherwise a city's pins land
         * behind the panel instead of in the visible half of the map.
         */
        centerOffsetX?: number;
    }>(),
    { centerOffsetX: 0 },
);

const search = useSearch();

const container = ref<HTMLDivElement | null>(null);

let L: typeof Leaflet | null = null;
let map: LeafletMap | null = null;
let cluster: MarkerClusterGroup | null = null;
// Markers by clinic id, so hovering a result card can highlight its pin.
const markers = new Map<number, Marker>();
// The marker currently raised above its neighbours, so it can be put back.
let lit: Marker | null = null;

/**
 * setView() centres on the whole container, but the panel hides its left edge,
 * so shift the centre left in projected pixels by half the covered width and
 * the target lands in the middle of what the user can actually see.
 */
function setViewOffset(center: [number, number], zoom: number): void {
    if (!map || !L) {
        return;
    }

    if (props.centerOffsetX <= 0) {
        map.setView(center, zoom);

        return;
    }

    const point = map
        .project(center, zoom)
        .subtract(L.point(props.centerOffsetX / 2, 0));

    map.setView(map.unproject(point, zoom), zoom);
}

async function load(): Promise<void> {
    if (map || !container.value) {
        return;
    }

    L = await loadLeaflet();
    await loadMarkerCluster();

    // The results panel covers the top-left, which is where Leaflet puts the
    // zoom control by default — move it to the free side.
    map = L.map(container.value, { zoomControl: false });
    L.control.zoom({ position: 'topright' }).addTo(map);

    setViewOffset(search.center.value, search.zoom.value);

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
    const entities: Record<string, string> = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    };

    return value.replace(/[&<>"']/g, (ch) => entities[ch] ?? ch);
}

function renderMarkers(): void {
    if (!L || !cluster) {
        return;
    }

    cluster.clearLayers();
    markers.clear();

    for (const clinic of search.clinics.value) {
        const marker = L.marker([clinic.lat, clinic.lng]);
        const directions = `https://www.google.com/maps/dir/?api=1&destination=${clinic.lat},${clinic.lng}`;
        marker.bindPopup(
            `<a href="${clinic.url}" class="font-medium underline">${escapeHtml(clinic.name)}</a>` +
                `<br><span>${escapeHtml(clinic.address)}</span>` +
                `<br><a href="${directions}" target="_blank" rel="noopener nofollow" class="underline">Direcții</a>`,
        );
        cluster.addLayer(marker);
        markers.set(clinic.id, marker);
    }

    highlight(search.selectedId.value);
}

/**
 * Highlights the hovered card's pin. Styling goes on a CSS class, never on
 * `transform` — Leaflet positions every marker with transform: translate3d(),
 * so overriding it would fling the pin across the map.
 *
 * A clustered marker has no element of its own, so light up the cluster that
 * stands in for it; otherwise hovering a card would do nothing at all until you
 * zoomed in far enough to break the clusters apart.
 */
function highlight(id: number | null): void {
    if (!cluster) {
        return;
    }

    container.value
        ?.querySelectorAll('.clinic-pin-active')
        .forEach((el) => el.classList.remove('clinic-pin-active'));
    lit?.setZIndexOffset(0);
    lit = null;

    if (id === null) {
        return;
    }

    const marker = markers.get(id);

    if (!marker) {
        return;
    }

    const visible = cluster.getVisibleParent(marker) ?? marker;
    visible.getElement()?.classList.add('clinic-pin-active');

    // Only a real marker needs raising; a cluster is already above its members.
    if (visible === marker) {
        marker.setZIndexOffset(1000);
        lit = marker;
    }
}

onMounted(() => void load());

// Re-render pins whenever the result set changes.
watch(search.clinics, () => renderMarkers());

watch(search.selectedId, (id) => highlight(id));

// Recentre when a city is chosen (setView fires moveend -> refetch by bounds).
watch([search.center, search.zoom], () => {
    if (map) {
        setViewOffset(search.center.value, search.zoom.value);
    }
});

onBeforeUnmount(() => {
    map?.remove();
    map = null;
    cluster = null;
    markers.clear();
});
</script>

<template>
    <!--
        `isolate` is load-bearing: Leaflet gives its own panes and controls
        z-index 200–1000, and `relative` alone (z-index: auto) does NOT create a
        stacking context, so those values escape into the root one and paint over
        anything below 1000 — which is every panel floating above this map.
        Isolating traps them in here.
    -->
    <div class="relative isolate h-full w-full overflow-hidden">
        <div
            ref="container"
            class="h-full w-full"
            role="application"
            aria-label="Hartă clinici"
        ></div>
    </div>
</template>

<!--
    Not scoped: Leaflet builds its marker elements outside Vue, so a scoped
    attribute selector would never match them.
-->
<style>
.clinic-pin-active {
    filter: drop-shadow(0 0 6px rgb(59 130 246 / 0.9)) saturate(1.4);
}
</style>
