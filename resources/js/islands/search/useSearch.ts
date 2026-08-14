import { computed, inject, provide, reactive, ref, watch } from 'vue';
import type { ComputedRef, InjectionKey, Ref } from 'vue';
import type {
    Bounds,
    CityOption,
    ClinicResult,
    Filters,
    SearchProps,
    ServiceOption,
} from './types';

/** Geographic centre of Romania — the map's home view before any city is picked. */
const ROMANIA_CENTER: [number, number] = [45.9432, 24.9668];

export interface SearchStore {
    filters: Filters;
    cities: CityOption[];
    services: ServiceOption[];
    clinics: Ref<ClinicResult[]>;
    count: ComputedRef<number>;
    loading: Ref<boolean>;
    failed: Ref<boolean>;
    bounds: Ref<Bounds | null>;
    center: Ref<[number, number]>;
    zoom: Ref<number>;
    selectedId: Ref<number | null>;
    setBounds(b: Bounds): void;
    selectCity(city: CityOption | null): void;
    resetFilters(): void;
    focus(id: number | null): void;
    refresh(): void;
}

const SearchKey: InjectionKey<SearchStore> = Symbol('search-store');

export function createSearchStore(props: SearchProps): SearchStore {
    const filters = reactive<Filters>({
        q: '',
        city_id: props.initial?.city_id ?? null,
        service_id: props.initial?.service_id ?? null,
        cnas: false,
        open_now: false,
        min_rating: 0,
    });

    const clinics = ref<ClinicResult[]>([]);
    const loading = ref(false);
    const failed = ref(false);
    const bounds = ref<Bounds | null>(null);
    const selectedId = ref<number | null>(null);

    const initialCity =
        props.cities.find((c) => c.id === filters.city_id) ?? null;
    const center = ref<[number, number]>(
        initialCity ? [initialCity.lat, initialCity.lng] : ROMANIA_CENTER,
    );
    // Zoom 6 (not 7) so the whole country's width fits the tall map panel on
    // load — otherwise eastern cities like Iași fall just off the edge.
    const zoom = ref(initialCity ? 13 : 6);

    const count = computed(() => clinics.value.length);

    // Monotonic request id: a slow response that resolves after a newer one must
    // not overwrite fresher results (the classic search race).
    let seq = 0;

    async function runFetch(): Promise<void> {
        const params = new URLSearchParams();

        if (filters.q) {
            params.set('q', filters.q);
        }

        if (filters.city_id) {
            params.set('city_id', String(filters.city_id));
        }

        if (filters.service_id) {
            params.set('service_id', String(filters.service_id));
        }

        if (filters.cnas) {
            params.set('cnas', '1');
        }

        if (filters.open_now) {
            params.set('open_now', '1');
        }

        if (filters.min_rating > 0) {
            params.set('min_rating', String(filters.min_rating));
        }

        if (bounds.value) {
            params.set('min_lat', String(bounds.value.min_lat));
            params.set('min_lng', String(bounds.value.min_lng));
            params.set('max_lat', String(bounds.value.max_lat));
            params.set('max_lng', String(bounds.value.max_lng));
        }

        const mine = ++seq;
        loading.value = true;
        failed.value = false;

        try {
            const response = await fetch(
                `${props.endpoint}?${params.toString()}`,
                {
                    headers: { Accept: 'application/json' },
                },
            );

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = (await response.json()) as { clinics: ClinicResult[] };

            if (mine === seq) {
                clinics.value = data.clinics;
            }
        } catch {
            if (mine === seq) {
                failed.value = true;
                clinics.value = [];
            }
        } finally {
            if (mine === seq) {
                loading.value = false;
            }
        }
    }

    let timer: ReturnType<typeof setTimeout> | undefined;
    function debouncedRefresh(): void {
        clearTimeout(timer);
        timer = setTimeout(() => void runFetch(), 250);
    }

    function setBounds(next: Bounds): void {
        bounds.value = next;
        void runFetch(); // map moves feel laggy if debounced
    }

    function selectCity(city: CityOption | null): void {
        filters.city_id = city?.id ?? null;

        if (city) {
            center.value = [city.lat, city.lng];
            zoom.value = 13;
        }
    }

    function resetFilters(): void {
        filters.q = '';
        filters.city_id = null;
        filters.service_id = null;
        filters.cnas = false;
        filters.open_now = false;
        filters.min_rating = 0;
    }

    // Any filter change refetches (debounced). Bounds changes are handled eagerly
    // in setBounds, so they are intentionally not watched here.
    watch(filters, () => debouncedRefresh());

    return {
        filters,
        cities: props.cities,
        services: props.services,
        clinics,
        count,
        loading,
        failed,
        bounds,
        center,
        zoom,
        selectedId,
        setBounds,
        selectCity,
        resetFilters,
        focus: (id) => {
            selectedId.value = id;
        },
        refresh: () => void runFetch(),
    };
}

export function provideSearch(store: SearchStore): void {
    provide(SearchKey, store);
}

export function useSearch(): SearchStore {
    const store = inject(SearchKey);

    if (!store) {
        throw new Error(
            'useSearch() must be called inside the <Search> island.',
        );
    }

    return store;
}
