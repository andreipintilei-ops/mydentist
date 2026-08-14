<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Pager from '@/components/admin/Pager.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

interface ReviewRow {
    id: number;
    clinic: string | null;
    author_name: string;
    rating: number;
    body: string | null;
    created_at: string;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

defineProps<{ reviews: Paginated<ReviewRow> }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administrare', href: '/admin' },
            { title: 'Recenzii', href: '/admin/recenzii' },
        ],
    },
});

function stars(rating: number): string {
    return '★'.repeat(rating) + '☆'.repeat(5 - rating);
}

function approve(id: number): void {
    router.post(`/admin/recenzii/${id}/aproba`, {}, { preserveScroll: true });
}

function reject(id: number): void {
    router.post(`/admin/recenzii/${id}/respinge`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Recenzii" />

    <div class="flex flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold tracking-tight">Recenzii de moderat</h1>

        <p
            v-if="reviews.data.length === 0"
            class="rounded-xl border p-8 text-center text-sm text-muted-foreground"
        >
            Nicio recenzie în așteptare.
        </p>

        <Card v-for="review in reviews.data" :key="review.id">
            <CardContent
                class="flex flex-wrap items-start justify-between gap-4 pt-6"
            >
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium">{{
                            review.author_name
                        }}</span>
                        <span class="text-amber-500" aria-hidden="true">{{
                            stars(review.rating)
                        }}</span>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ review.clinic }} · {{ review.created_at }}
                    </p>
                    <p v-if="review.body" class="mt-1 text-sm">
                        {{ review.body }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button size="sm" @click="approve(review.id)"
                        >Publică</Button
                    >
                    <Button
                        size="sm"
                        variant="outline"
                        @click="reject(review.id)"
                        >Ascunde</Button
                    >
                </div>
            </CardContent>
        </Card>

        <Pager
            :prev-url="reviews.prev_page_url"
            :next-url="reviews.next_page_url"
            :current="reviews.current_page"
            :last="reviews.last_page"
        />
    </div>
</template>
