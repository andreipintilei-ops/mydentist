<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Kpis {
    published: number;
    pending: number;
    draft: number;
    rejected: number;
    open_claims: number;
    pending_reviews: number;
}

const props = defineProps<{
    kpis: Kpis;
    registrations: { label: string; count: number }[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Administrare', href: '/admin' }] },
});

const cards = computed(() => [
    {
        label: 'Publicate',
        value: props.kpis.published,
        href: '/admin/clinici?status=published',
    },
    {
        label: 'În așteptare',
        value: props.kpis.pending,
        href: '/admin/moderare',
    },
    {
        label: 'Ciorne',
        value: props.kpis.draft,
        href: '/admin/clinici?status=draft',
    },
    {
        label: 'Respinse',
        value: props.kpis.rejected,
        href: '/admin/clinici?status=rejected',
    },
    {
        label: 'Revendicări deschise',
        value: props.kpis.open_claims,
        href: '/admin/revendicari',
    },
    {
        label: 'Recenzii de moderat',
        value: props.kpis.pending_reviews,
        href: '/admin/recenzii',
    },
]);

const maxCount = computed(() =>
    Math.max(1, ...props.registrations.map((r) => r.count)),
);
</script>

<template>
    <Head title="Administrare" />

    <div class="flex flex-col gap-6 p-4">
        <h1 class="text-2xl font-bold tracking-tight">Administrare</h1>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
            <Link v-for="card in cards" :key="card.label" :href="card.href">
                <Card class="h-full transition-colors hover:border-primary">
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-xs font-medium text-muted-foreground"
                            >{{ card.label }}</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <span class="text-3xl font-bold">{{ card.value }}</span>
                    </CardContent>
                </Card>
            </Link>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-base"
                    >Înregistrări noi (ultimele 8 săptămâni)</CardTitle
                >
            </CardHeader>
            <CardContent>
                <div class="flex h-48 items-end gap-2">
                    <div
                        v-for="week in registrations"
                        :key="week.label"
                        class="flex flex-1 flex-col items-center gap-2"
                    >
                        <div class="flex w-full flex-1 items-end">
                            <div
                                class="w-full rounded-t bg-primary/80"
                                :style="{
                                    height: `${(week.count / maxCount) * 100}%`,
                                }"
                                :title="`${week.count}`"
                            ></div>
                        </div>
                        <span class="text-xs text-muted-foreground">{{
                            week.label
                        }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
