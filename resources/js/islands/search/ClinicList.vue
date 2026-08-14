<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { useSearch } from './useSearch';

const search = useSearch();

function stars(rating: number): string {
    const filled = Math.round(rating);

    return '★'.repeat(filled) + '☆'.repeat(5 - filled);
}
</script>

<template>
    <div class="flex flex-col">
        <div v-if="search.failed.value" class="p-4 text-sm text-destructive">
            Nu am putut încărca clinicile. Reîncearcă.
        </div>

        <div
            v-else-if="
                !search.loading.value && search.clinics.value.length === 0
            "
            class="p-4 text-sm text-muted-foreground"
        >
            Nicio clinică pentru filtrele curente. Micșorează harta sau elimină
            un filtru.
        </div>

        <ul v-else class="divide-y">
            <li
                v-for="clinic in search.clinics.value"
                :key="clinic.id"
                class="p-4 transition-colors hover:bg-accent/50"
                :class="{
                    'bg-accent/60': search.selectedId.value === clinic.id,
                }"
                @mouseenter="search.focus(clinic.id)"
                @mouseleave="search.focus(null)"
            >
                <a :href="clinic.url" class="flex flex-col gap-1">
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-medium hover:underline">{{
                            clinic.name
                        }}</span>
                        <Badge
                            v-if="clinic.cnas"
                            variant="secondary"
                            class="shrink-0"
                            >CNAS</Badge
                        >
                    </div>

                    <span class="text-sm text-muted-foreground"
                        >{{ clinic.address }}, {{ clinic.city }}</span
                    >

                    <span v-if="clinic.rating_count > 0" class="text-sm">
                        <span class="text-amber-500" aria-hidden="true">{{
                            stars(clinic.rating_avg)
                        }}</span>
                        <span class="text-muted-foreground">
                            {{ clinic.rating_avg.toFixed(1) }} ({{
                                clinic.rating_count
                            }})
                        </span>
                    </span>

                    <span
                        v-if="clinic.services.length"
                        class="mt-1 flex flex-wrap gap-1"
                    >
                        <Badge
                            v-for="name in clinic.services.slice(0, 3)"
                            :key="name"
                            variant="outline"
                        >
                            {{ name }}
                        </Badge>
                    </span>
                </a>
            </li>
        </ul>
    </div>
</template>
