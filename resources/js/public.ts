import type { Component } from 'vue';
import { createApp } from 'vue';

/**
 * Island mounter for the public (Blade-rendered) side of the site.
 *
 * Public pages are server-rendered Blade so that crawlers get complete HTML.
 * Anything genuinely interactive — the map, the filter drawer, the city
 * combobox — is mounted here as an isolated Vue app. There is deliberately no
 * Inertia runtime on this entrypoint.
 *
 * Usage from Blade:
 *   <div data-vue="ClinicMap" data-props='@json(['clinics' => $clinics])'></div>
 */
const registry: Record<string, () => Promise<{ default: Component }>> = {
    // Dev-only, rendered by the /_ui-parity route.
    UiParity: () => import('./islands/UiParity.vue'),
    // The /cauta search app: one cohesive island composing the filters, the
    // Leaflet map and the results list over a shared reactive store.
    Search: () => import('./islands/Search.vue'),
    // Single-clinic locator map on the /clinica/{slug} profile.
    ClinicLocationMap: () => import('./islands/ClinicLocationMap.vue'),
    // Public light/dark/system theme toggle in the header.
    ThemeToggle: () => import('./islands/ThemeToggle.vue'),
};

function parseProps(el: HTMLElement): Record<string, unknown> {
    const raw = el.dataset.props;

    if (!raw) {
        return {};
    }

    try {
        return JSON.parse(raw) as Record<string, unknown>;
    } catch (error) {
        console.error(
            `[islands] Invalid data-props JSON on "${el.dataset.vue}" island.`,
            error,
        );

        return {};
    }
}

async function mountIsland(el: HTMLElement): Promise<void> {
    const name = el.dataset.vue;

    if (!name) {
        return;
    }

    const loader = registry[name];

    if (!loader) {
        console.warn(`[islands] No component registered under "${name}".`);

        return;
    }

    const { default: component } = await loader();

    createApp(component, parseProps(el)).mount(el);
}

function mountAll(): void {
    document.querySelectorAll<HTMLElement>('[data-vue]').forEach((el) => {
        void mountIsland(el);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountAll, { once: true });
} else {
    mountAll();
}
