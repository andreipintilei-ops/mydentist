<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pager from '@/components/admin/Pager.vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';

interface ClinicRow {
    id: number;
    name: string;
    city: string | null;
    status: string;
    status_label: string;
    rating_avg: number;
    rating_count: number;
    is_demo: boolean;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

const props = defineProps<{
    clinics: Paginated<ClinicRow>;
    filters: { q: string; status: string; sort: string; dir: string };
    statuses: { value: string; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administrare', href: '/admin' },
            { title: 'Clinici', href: '/admin/clinici' },
        ],
    },
});

const search = ref(props.filters.q);
const status = ref(props.filters.status);

const selectClass =
    'border-input bg-background h-9 rounded-md border px-3 py-1 text-sm shadow-xs outline-none';

let debounce: ReturnType<typeof setTimeout> | undefined;
watch([search, status], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(
            '/admin/clinici',
            { q: search.value, status: status.value },
            { preserveState: true, replace: true },
        );
    }, 300);
});

function sortBy(column: string): void {
    const dir =
        props.filters.sort === column && props.filters.dir === 'asc'
            ? 'desc'
            : 'asc';
    router.get(
        '/admin/clinici',
        { q: search.value, status: status.value, sort: column, dir },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Clinici" />

    <div class="flex flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold tracking-tight">Clinici</h1>

        <div class="flex flex-wrap gap-3">
            <Input
                v-model="search"
                type="search"
                placeholder="Caută nume sau adresă…"
                class="max-w-xs"
            />
            <select v-model="status" :class="selectClass">
                <option value="">Toate statusurile</option>
                <option v-for="s in statuses" :key="s.value" :value="s.value">
                    {{ s.label }}
                </option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th
                            class="cursor-pointer px-4 py-2"
                            @click="sortBy('name')"
                        >
                            Nume
                        </th>
                        <th class="px-4 py-2">Oraș</th>
                        <th
                            class="cursor-pointer px-4 py-2"
                            @click="sortBy('status')"
                        >
                            Status
                        </th>
                        <th
                            class="cursor-pointer px-4 py-2"
                            @click="sortBy('rating_avg')"
                        >
                            Rating
                        </th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="clinic in clinics.data"
                        :key="clinic.id"
                        class="border-t"
                    >
                        <td class="px-4 py-2">
                            <span class="font-medium">{{ clinic.name }}</span>
                            <Badge
                                v-if="clinic.is_demo"
                                variant="outline"
                                class="ml-2"
                                >demo</Badge
                            >
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ clinic.city }}
                        </td>
                        <td class="px-4 py-2">
                            <Badge
                                :variant="
                                    clinic.status === 'published'
                                        ? 'default'
                                        : clinic.status === 'rejected'
                                          ? 'destructive'
                                          : 'secondary'
                                "
                                >{{ clinic.status_label }}</Badge
                            >
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ clinic.rating_avg.toFixed(1) }} ({{
                                clinic.rating_count
                            }})
                        </td>
                        <td class="px-4 py-2 text-right">
                            <Link
                                :href="`/admin/clinici/${clinic.id}/editeaza`"
                                class="text-primary hover:underline"
                                >Editează</Link
                            >
                        </td>
                    </tr>
                    <tr v-if="clinics.data.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Nicio clinică găsită.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pager
            :prev-url="clinics.prev_page_url"
            :next-url="clinics.next_page_url"
            :current="clinics.current_page"
            :last="clinics.last_page"
        />
    </div>
</template>
