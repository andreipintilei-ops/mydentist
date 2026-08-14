<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Pager from '@/components/admin/Pager.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

interface ClaimRow {
    id: number;
    status: string;
    status_label: string;
    clinic: string | null;
    user: string | null;
    user_email: string | null;
    claimant_email: string;
    message: string | null;
    created_at: string;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

defineProps<{ claims: Paginated<ClaimRow> }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administrare', href: '/admin' },
            { title: 'Revendicări', href: '/admin/revendicari' },
        ],
    },
});

function approve(id: number): void {
    router.post(
        `/admin/revendicari/${id}/aproba`,
        {},
        { preserveScroll: true },
    );
}

function reject(id: number): void {
    router.post(
        `/admin/revendicari/${id}/respinge`,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Revendicări" />

    <div class="flex flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold tracking-tight">Revendicări</h1>

        <p
            v-if="claims.data.length === 0"
            class="rounded-xl border p-8 text-center text-sm text-muted-foreground"
        >
            Nicio revendicare de analizat.
        </p>

        <Card v-for="claim in claims.data" :key="claim.id">
            <CardContent
                class="flex flex-wrap items-start justify-between gap-4 pt-6"
            >
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium">{{ claim.clinic }}</span>
                        <Badge
                            :variant="
                                claim.status === 'verified'
                                    ? 'default'
                                    : 'secondary'
                            "
                            >{{ claim.status_label }}</Badge
                        >
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ claim.user }} ({{ claim.user_email }}) · email
                        verificare: {{ claim.claimant_email }}
                    </p>
                    <p v-if="claim.message" class="mt-1 text-sm">
                        „{{ claim.message }}”
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button
                        size="sm"
                        :disabled="claim.status !== 'verified'"
                        @click="approve(claim.id)"
                        >Aprobă</Button
                    >
                    <Button
                        size="sm"
                        variant="outline"
                        @click="reject(claim.id)"
                        >Respinge</Button
                    >
                </div>
            </CardContent>
        </Card>

        <Pager
            :prev-url="claims.prev_page_url"
            :next-url="claims.next_page_url"
            :current="claims.current_page"
            :last="claims.last_page"
        />
    </div>
</template>
