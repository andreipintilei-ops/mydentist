export interface CityOption {
    id: number;
    name: string;
    slug: string;
    lat: number;
    lng: number;
}

export interface ServiceOption {
    id: number;
    name: string;
    slug: string;
}

/** A clinic as returned by the /cauta/clinici JSON feed. */
export interface ClinicResult {
    id: number;
    name: string;
    url: string;
    lat: number;
    lng: number;
    address: string;
    city: string | null;
    cnas: boolean;
    rating_avg: number;
    rating_count: number;
    services: string[];
}

export interface Bounds {
    min_lat: number;
    min_lng: number;
    max_lat: number;
    max_lng: number;
}

/** An active filter shown as a removable chip in the search bar. */
export interface FilterChip {
    key: string;
    label: string;
    clear: () => void;
}

export interface Filters {
    q: string;
    city_id: number | null;
    service_id: number | null;
    cnas: boolean;
    open_now: boolean;
    min_rating: number;
}

/** Props the Blade shell passes into the Search island via data-props. */
export interface SearchProps {
    endpoint: string;
    cities: CityOption[];
    services: ServiceOption[];
    initial?: Partial<Pick<Filters, 'city_id' | 'service_id'>>;
}
