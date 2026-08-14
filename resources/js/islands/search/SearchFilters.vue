<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CityCombobox from './CityCombobox.vue';
import { useSearch } from './useSearch';

const search = useSearch();

const selectClass =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

// select proxies: option 0 means "no filter" -> stored as null.
const serviceId = computed({
    get: () => search.filters.service_id ?? 0,
    set: (value: number) => {
        search.filters.service_id = value || null;
    },
});

const ratingOptions = [
    { value: 0, label: 'Orice rating' },
    { value: 3, label: '3+ stele' },
    { value: 4, label: '4+ stele' },
    { value: 4.5, label: '4.5+ stele' },
];

function setCnas(value: boolean | 'indeterminate'): void {
    search.filters.cnas = value === true;
}

function setOpenNow(value: boolean | 'indeterminate'): void {
    search.filters.open_now = value === true;
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-1.5">
            <Label for="q">Caută</Label>
            <Input
                id="q"
                v-model="search.filters.q"
                type="search"
                placeholder="Nume clinică sau adresă…"
            />
        </div>

        <div class="flex flex-col gap-1.5">
            <Label>Oraș</Label>
            <CityCombobox
                :cities="search.cities"
                @select="search.selectCity($event)"
            />
        </div>

        <div class="flex flex-col gap-1.5">
            <Label for="service">Serviciu</Label>
            <select
                id="service"
                v-model.number="serviceId"
                :class="selectClass"
            >
                <option :value="0">Toate serviciile</option>
                <option
                    v-for="service in search.services"
                    :key="service.id"
                    :value="service.id"
                >
                    {{ service.name }}
                </option>
            </select>
        </div>

        <div class="flex flex-col gap-1.5">
            <Label for="rating">Rating minim</Label>
            <select
                id="rating"
                v-model.number="search.filters.min_rating"
                :class="selectClass"
            >
                <option
                    v-for="option in ratingOptions"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <Checkbox
                id="cnas"
                :model-value="search.filters.cnas"
                @update:model-value="setCnas"
            />
            <Label for="cnas" class="font-normal">Contract CNAS</Label>
        </div>

        <div class="flex items-center gap-2">
            <Checkbox
                id="open_now"
                :model-value="search.filters.open_now"
                @update:model-value="setOpenNow"
            />
            <Label for="open_now" class="font-normal">Deschis acum</Label>
        </div>

        <Button
            variant="ghost"
            size="sm"
            class="self-start"
            @click="search.resetFilters()"
        >
            Resetează filtrele
        </Button>
    </div>
</template>
