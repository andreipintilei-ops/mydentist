import type * as Leaflet from 'leaflet';

/**
 * Lazily loads Leaflet once and returns the mutable L object with its default
 * marker images wired up.
 *
 * Two non-obvious things live here so no island has to rediscover them:
 *   1. We return leaflet.default — the live, mutable L object — NOT the ES module
 *      namespace, which is sealed (plugins can't attach to it).
 *   2. We set window.L before any plugin loads, because Leaflet plugins are UMD
 *      and attach onto the global L (see loadMarkerCluster).
 *   3. Default marker icon URLs must be set explicitly or bundlers ship broken
 *      image paths and markers render blank.
 */
let cached: typeof Leaflet | null = null;

export async function loadLeaflet(): Promise<typeof Leaflet> {
    if (cached) {
        return cached;
    }

    const leaflet = await import('leaflet');
    const L = leaflet.default;

    (window as unknown as { L: typeof Leaflet }).L = L;

    await import('leaflet/dist/leaflet.css');

    const [icon, icon2x, shadow] = await Promise.all([
        import('leaflet/dist/images/marker-icon.png'),
        import('leaflet/dist/images/marker-icon-2x.png'),
        import('leaflet/dist/images/marker-shadow.png'),
    ]);

    L.Icon.Default.mergeOptions({
        iconUrl: icon.default,
        iconRetinaUrl: icon2x.default,
        shadowUrl: shadow.default,
    });

    cached = L;

    return L;
}

/**
 * Loads the markercluster plugin. loadLeaflet() must have run first so window.L
 * is set — the plugin extends that global.
 */
export async function loadMarkerCluster(): Promise<void> {
    await import('leaflet.markercluster');
    await import('leaflet.markercluster/dist/MarkerCluster.css');
    await import('leaflet.markercluster/dist/MarkerCluster.Default.css');
}
