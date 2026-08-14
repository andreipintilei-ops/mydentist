<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

defineProps<{
    threshold: number;
    stats: { published: number; demo: number; stat_pairs: number };
    thin: { city: string | null; service: string | null; count: number }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administrare', href: '/admin' },
            { title: 'SEO', href: '/admin/seo' },
        ],
    },
});

function rebuildStats(): void {
    router.post('/admin/seo/rebuild-stats', {}, { preserveScroll: true });
}

function purgeDemo(): void {
    if (
        window.confirm(
            'Ștergi toate clinicile demo? Acțiunea nu poate fi anulată.',
        )
    ) {
        router.post('/admin/seo/purge-demo', {}, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="SEO" />

    <div class="flex flex-col gap-6 p-4">
        <h1 class="text-2xl font-bold tracking-tight">Unelte SEO</h1>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-xs text-muted-foreground"
                        >Clinici publicate</CardTitle
                    >
                </CardHeader>
                <CardContent
                    ><span class="text-2xl font-bold">{{
                        stats.published
                    }}</span></CardContent
                >
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-xs text-muted-foreground"
                        >Clinici demo</CardTitle
                    >
                </CardHeader>
                <CardContent
                    ><span class="text-2xl font-bold">{{
                        stats.demo
                    }}</span></CardContent
                >
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-xs text-muted-foreground"
                        >Perechi oraș × serviciu</CardTitle
                    >
                </CardHeader>
                <CardContent
                    ><span class="text-2xl font-bold">{{
                        stats.stat_pairs
                    }}</span></CardContent
                >
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Întreținere</CardTitle>
                <CardDescription>
                    Recalculează statisticile city × service sau șterge datele
                    demo înainte de lansare.
                </CardDescription>
            </CardHeader>
            <CardContent class="flex flex-wrap gap-3">
                <Button @click="rebuildStats">Recalculează statisticile</Button>
                <Button variant="destructive" @click="purgeDemo"
                    >Șterge clinicile demo</Button
                >
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Pagini subțiri</CardTitle>
                <CardDescription>
                    Perechi oraș × serviciu sub pragul de
                    {{ threshold }} clinici — acestea redirecționează către
                    pagina orașului în loc să se afișeze.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <p
                    v-if="thin.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nicio pagină subțire.
                </p>
                <ul v-else class="flex flex-col gap-1 text-sm">
                    <li
                        v-for="(row, i) in thin"
                        :key="i"
                        class="flex justify-between border-b py-1 last:border-0"
                    >
                        <span>{{ row.service }} în {{ row.city }}</span>
                        <span class="text-muted-foreground">{{
                            row.count
                        }}</span>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>
</template>
