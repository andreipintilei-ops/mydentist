<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface HourRow {
    day_of_week: number;
    is_closed: boolean;
    opens_at: string;
    closes_at: string;
}

const props = defineProps<{
    clinic: {
        id: number;
        name: string;
        status_label: string;
        description: string | null;
        phone: string | null;
        email: string | null;
        website: string | null;
        cnas_contract: boolean;
    };
    services: { id: number; name: string }[];
    selectedServices: number[];
    hours: HourRow[];
    days: Record<number, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Clinicile mele', href: '/cont' },
            { title: 'Editează', href: '#' },
        ],
    },
});

const form = useForm({
    description: props.clinic.description ?? '',
    phone: props.clinic.phone ?? '',
    email: props.clinic.email ?? '',
    website: props.clinic.website ?? '',
    cnas_contract: props.clinic.cnas_contract,
    services: [...props.selectedServices],
    hours: props.hours.map((h) => ({ ...h })),
});

const controlClass =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:opacity-50';

function submit(): void {
    form.put(`/cont/clinici/${props.clinic.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Editează ${clinic.name}`" />

    <form class="flex max-w-3xl flex-col gap-6 p-4" @submit.prevent="submit">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ clinic.name }}</h1>
            <p class="text-sm text-muted-foreground">
                Status: {{ clinic.status_label }}
            </p>
        </div>

        <div class="flex flex-col gap-1.5">
            <Label for="description">Descriere</Label>
            <textarea
                id="description"
                v-model="form.description"
                rows="5"
                :class="controlClass"
            ></textarea>
            <InputError :message="form.errors.description" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-1.5">
                <Label for="phone">Telefon</Label>
                <Input id="phone" v-model="form.phone" />
                <InputError :message="form.errors.phone" />
            </div>
            <div class="flex flex-col gap-1.5">
                <Label for="email">Email</Label>
                <Input id="email" v-model="form.email" type="email" />
                <InputError :message="form.errors.email" />
            </div>
        </div>

        <div class="flex flex-col gap-1.5">
            <Label for="website">Site web</Label>
            <Input
                id="website"
                v-model="form.website"
                type="url"
                placeholder="https://"
            />
            <InputError :message="form.errors.website" />
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input
                type="checkbox"
                v-model="form.cnas_contract"
                class="size-4 rounded border-input"
            />
            Contract CNAS
        </label>

        <fieldset class="flex flex-col gap-2">
            <legend class="mb-1 text-sm font-medium">Servicii</legend>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <label
                    v-for="service in services"
                    :key="service.id"
                    class="flex items-center gap-2 text-sm"
                >
                    <input
                        type="checkbox"
                        :value="service.id"
                        v-model="form.services"
                        class="size-4 rounded border-input"
                    />
                    {{ service.name }}
                </label>
            </div>
        </fieldset>

        <fieldset class="flex flex-col gap-2">
            <legend class="mb-1 text-sm font-medium">Program</legend>
            <div class="flex flex-col gap-2">
                <div
                    v-for="row in form.hours"
                    :key="row.day_of_week"
                    class="grid grid-cols-[6rem_auto_1fr] items-center gap-3"
                >
                    <span class="text-sm">{{ days[row.day_of_week] }}</span>
                    <label class="flex items-center gap-1.5 text-sm">
                        <input
                            type="checkbox"
                            v-model="row.is_closed"
                            class="size-4 rounded border-input"
                        />
                        Închis
                    </label>
                    <div class="flex items-center gap-2">
                        <input
                            type="time"
                            v-model="row.opens_at"
                            :disabled="row.is_closed"
                            :class="controlClass"
                        />
                        <span class="text-muted-foreground">–</span>
                        <input
                            type="time"
                            v-model="row.closes_at"
                            :disabled="row.is_closed"
                            :class="controlClass"
                        />
                    </div>
                </div>
            </div>
        </fieldset>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">Salvează</Button>
            <a
                href="/cont"
                class="text-sm text-muted-foreground hover:underline"
                >Înapoi</a
            >
        </div>
    </form>
</template>
