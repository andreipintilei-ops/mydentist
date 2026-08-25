import { computed, inject, provide, reactive, ref, watch } from 'vue';
import type { ComputedRef, InjectionKey, Ref } from 'vue';
import type {
    Bounds,
    CityOption,
    ClinicResult,
    FilterChip,
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
    /** The server hit its result ceiling, so `count` is a floor, not a total. */
    capped: Ref<boolean>;
    loading: Ref<boolean>;
    failed: Ref<boolean>;
    bounds: Ref<Bounds | null>;
    center: Ref<[number, number]>;
    zoom: Ref<number>;
    selectedId: Ref<number | null>;
    activeFilterCount: ComputedRef<number>;
    chips: ComputedRef<FilterChip[]>;
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
    const capped = ref(false);
    const loading = ref(false);
    const failed = ref(false);
    const bounds = ref<Bounds | null>(null);
    const selectedId = ref<number | null>(null);

    const initialCity =
        props.cities.find((c) => c.id === filters.city_id) ?? null;
    const center = ref<[number, number]>(
        initialCity ? [initialCity.lat, initialCity.lng] : ROMANIA_CENTER,
    );
    // Zoom 6 (not 7) so the whole country's width fits the map on load —
    // otherwise eastern cities like Iași fall just off the edge.
    const zoom = ref(initialCity ? 13 : 6);

    const count = computed(() => clinics.value.length);

    // Only the filters that live behind the "Filtre" button. `q` and the city
    // have their own controls in the bar, so counting them would double-report.
    const activeFilterCount = computed(() => {
        let n = 0;

        if (filters.service_id) {
            n++;
        }

        if (filters.cnas) {
            n++;
        }

        if (filters.open_now) {
            n++;
        }

        if (filters.min_rating > 0) {
            n++;
        }

        return n;
    });

    // Active filters as removable chips, so their state stays visible on the map
    // instead of hiding inside a closed panel.
    const chips = computed<FilterChip[]>(() => {
        const list: FilterChip[] = [];
        const city = props.cities.find((c) => c.id === filters.city_id);

        if (city) {
            list.push({
                key: 'city',
                label: city.name,
                clear: () => selectCity(null),
            });
        }

        const service = props.services.find((s) => s.id === filters.service_id);

        if (service) {
            list.push({
                key: 'service',
                label: service.name,
                clear: () => {
                    filters.service_id = null;
                },
            });
        }

        if (filters.min_rating > 0) {
            list.push({
                key: 'rating',
                label: `${filters.min_rating}+ stele`,
                clear: () => {
                    filters.min_rating = 0;
                },
            });
        }

        if (filters.cnas) {
            list.push({
                key: 'cnas',
                label: 'Contract CNAS',
                clear: () => {
                    filters.cnas = false;
                },
            });
        }

        if (filters.open_now) {
            list.push({
                key: 'open_now',
                label: 'Deschis acum',
                clear: () => {
                    filters.open_now = false;
                },
            });
        }

        return list;
    });

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

            const data = (await response.json()) as {
                clinics: ClinicResult[];
                capped?: boolean;
            };

            if (mine === seq) {
                clinics.value = data.clinics;
                capped.value = data.capped === true;
            }
        } catch {
            if (mine === seq) {
                failed.value = true;
                clinics.value = [];
                capped.value = false;
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
        // Fires once per completed pan/zoom (moveend), not per frame, and map
        // moves feel laggy if debounced — so search straight away.
        void runFetch();
    }

    function selectCity(city: CityOption | null): void {
        filters.city_id = city?.id ?? null;

        if (city) {
            center.value = [city.lat, city.lng];
            zoom.value = 13;
            // Drop the old viewport so the filter-driven refetch is city-wide
            // rather than briefly searching the previous city's box; the
            // recentre's moveend then re-establishes bounds and searches.
            bounds.value = null;
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

    // Any filter change refetches (debounced) within the current viewport.
    // Bounds changes are handled in setBounds, so they are not watched here.
    watch(filters, () => debouncedRefresh());

    return {
        filters,
        cities: props.cities,
        services: props.services,
        clinics,
        count,
        capped,
        loading,
        failed,
        bounds,
        center,
        zoom,
        selectedId,
        activeFilterCount,
        chips,
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
