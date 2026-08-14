<script setup lang="ts">
import { computed, ref } from 'vue';
import { Input } from '@/components/ui/input';
import type { CityOption } from './types';

const props = withDefaults(
    defineProps<{
        cities: CityOption[];
        placeholder?: string;
    }>(),
    { placeholder: 'Oraș…' },
);

const emit = defineEmits<{
    select: [city: CityOption | null];
}>();

const query = ref('');
const open = ref(false);
const activeIndex = ref(-1);

/** Fold diacritics + case so "iasi" matches "Iași" client-side too. */
function fold(value: string): string {
    return value
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLowerCase();
}

const matches = computed(() => {
    const needle = fold(query.value.trim());

    if (!needle) {
        return props.cities.slice(0, 8);
    }

    return props.cities
        .filter((c) => fold(c.name).includes(needle))
        .slice(0, 8);
});

function choose(city: CityOption): void {
    query.value = city.name;
    open.value = false;
    activeIndex.value = -1;
    emit('select', city);
}

function clear(): void {
    query.value = '';
    emit('select', null);
}

function onInput(): void {
    open.value = true;
    activeIndex.value = -1;

    // Clearing the box clears the filter.
    if (query.value.trim() === '') {
        emit('select', null);
    }
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        open.value = true;
        activeIndex.value = Math.min(
            activeIndex.value + 1,
            matches.value.length - 1,
        );
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex.value = Math.max(activeIndex.value - 1, 0);
    } else if (event.key === 'Enter') {
        const city = matches.value[activeIndex.value];

        if (open.value && city) {
            event.preventDefault();
            choose(city);
        }
    } else if (event.key === 'Escape') {
        open.value = false;
    }
}
</script>

<template>
    <div class="relative">
        <Input
            v-model="query"
            type="text"
            :placeholder="placeholder"
            autocomplete="off"
            role="combobox"
            :aria-expanded="open"
            aria-label="Caută orașul"
            @input="onInput"
            @focus="open = true"
            @keydown="onKeydown"
            @blur="open = false"
        />

        <button
            v-if="query"
            type="button"
            class="absolute end-2 top-1/2 -translate-y-1/2 text-sm text-muted-foreground hover:text-foreground"
            aria-label="Șterge orașul"
            @mousedown.prevent="clear"
        >
            ✕
        </button>

        <ul
            v-if="open && matches.length"
            class="absolute z-20 mt-1 w-full overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md"
            role="listbox"
        >
            <li
                v-for="(city, i) in matches"
                :key="city.id"
                role="option"
                :aria-selected="i === activeIndex"
                class="cursor-pointer px-3 py-2 text-sm"
                :class="
                    i === activeIndex
                        ? 'bg-accent text-accent-foreground'
                        : 'hover:bg-accent hover:text-accent-foreground'
                "
                @mousedown.prevent="choose(city)"
                @mouseenter="activeIndex = i"
            >
                {{ city.name }}
            </li>
        </ul>
    </div>
</template>
