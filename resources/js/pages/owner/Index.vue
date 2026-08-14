<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface OwnedClinic {
    id: number;
    name: string;
    slug: string;
    status: string;
    status_label: string;
    city: string | null;
    public_url: string;
}

defineProps<{ clinics: OwnedClinic[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Clinicile mele', href: '/cont' }],
    },
});

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'outline' | 'destructive' {
    if (status === 'published') {
        return 'default';
    }

    if (status === 'rejected') {
        return 'destructive';
    }

    return 'secondary';
}
</script>

<template>
    <Head title="Clinicile mele" />

    <div class="flex flex-col gap-4 p-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Clinicile mele</h1>
            <p class="text-sm text-muted-foreground">
                Clinicile pe care le administrezi. Editează detalii, servicii și
                program.
            </p>
        </div>

        <div
            v-if="clinics.length === 0"
            class="rounded-xl border p-8 text-center text-sm text-muted-foreground"
        >
            Nu administrezi încă nicio clinică. Revendică o clinică existentă
            din pagina ei publică sau
            <a href="/adauga-clinica" class="text-primary hover:underline"
                >adaugă una nouă</a
            >.
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="clinic in clinics" :key="clinic.id" class="h-full">
                <CardHeader>
                    <div class="flex items-start justify-between gap-2">
                        <CardTitle class="text-base">{{
                            clinic.name
                        }}</CardTitle>
                        <Badge :variant="statusVariant(clinic.status)">{{
                            clinic.status_label
                        }}</Badge>
                    </div>
                    <CardDescription>{{ clinic.city }}</CardDescription>
                </CardHeader>
                <CardContent class="flex gap-2">
                    <Button as-child size="sm">
                        <Link :href="`/cont/clinici/${clinic.id}/editeaza`"
                            >Editează</Link
                        >
                    </Button>
                    <Button as-child size="sm" variant="outline">
                        <a :href="clinic.public_url" target="_blank">Vezi</a>
                    </Button>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
