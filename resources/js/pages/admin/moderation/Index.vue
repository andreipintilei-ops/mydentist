<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Pager from '@/components/admin/Pager.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

interface PendingClinic {
    id: number;
    name: string;
    city: string | null;
    address: string;
    email: string | null;
    phone: string | null;
    created_at: string;
    geocoded: boolean;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

defineProps<{ clinics: Paginated<PendingClinic> }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administrare', href: '/admin' },
            { title: 'Moderare', href: '/admin/moderare' },
        ],
    },
});

function approve(id: number): void {
    router.post(`/admin/moderare/${id}/aproba`, {}, { preserveScroll: true });
}

function reject(id: number): void {
    const reason = window.prompt('Motiv (opțional) pentru respingere:') ?? '';
    router.post(
        `/admin/moderare/${id}/respinge`,
        { reason },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Moderare" />

    <div class="flex flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold tracking-tight">Clinici în așteptare</h1>

        <p
            v-if="clinics.data.length === 0"
            class="rounded-xl border p-8 text-center text-sm text-muted-foreground"
        >
            Nimic de moderat. 🎉
        </p>

        <Card v-for="clinic in clinics.data" :key="clinic.id">
            <CardContent
                class="flex flex-wrap items-start justify-between gap-4 pt-6"
            >
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium">{{ clinic.name }}</span>
                        <Badge v-if="!clinic.geocoded" variant="destructive"
                            >Fără coordonate</Badge
                        >
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ clinic.address }}, {{ clinic.city }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ clinic.email }} · {{ clinic.phone }} ·
                        {{ clinic.created_at }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button size="sm" @click="approve(clinic.id)"
                        >Publică</Button
                    >
                    <Button
                        size="sm"
                        variant="outline"
                        @click="reject(clinic.id)"
                        >Respinge</Button
                    >
                </div>
            </CardContent>
        </Card>

        <Pager
            :prev-url="clinics.prev_page_url"
            :next-url="clinics.next_page_url"
            :current="clinics.current_page"
            :last="clinics.last_page"
        />
    </div>
</template>
