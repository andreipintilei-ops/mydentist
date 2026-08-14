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
        status: string;
        city_id: number;
        address: string;
        lat: number;
        lng: number;
        phone: string | null;
        email: string | null;
        website: string | null;
        description: string | null;
        cnas_contract: boolean;
    };
    cities: { id: number; name: string }[];
    services: { id: number; name: string }[];
    selectedServices: number[];
    hours: HourRow[];
    days: Record<number, string>;
    statuses: { value: string; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administrare', href: '/admin' },
            { title: 'Clinici', href: '/admin/clinici' },
            { title: 'Editează', href: '#' },
        ],
    },
});

const form = useForm({
    name: props.clinic.name,
    status: props.clinic.status,
    city_id: props.clinic.city_id,
    address: props.clinic.address,
    lat: props.clinic.lat,
    lng: props.clinic.lng,
    phone: props.clinic.phone ?? '',
    email: props.clinic.email ?? '',
    website: props.clinic.website ?? '',
    description: props.clinic.description ?? '',
    cnas_contract: props.clinic.cnas_contract,
    services: [...props.selectedServices],
    hours: props.hours.map((h) => ({ ...h })),
});

const controlClass =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:opacity-50';

function submit(): void {
    form.put(`/admin/clinici/${props.clinic.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Editează ${clinic.name}`" />

    <form class="flex max-w-3xl flex-col gap-6 p-4" @submit.prevent="submit">
        <h1 class="text-2xl font-bold tracking-tight">Editează clinica</h1>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-1.5">
                <Label for="name">Nume</Label>
                <Input id="name" v-model="form.name" />
                <InputError :message="form.errors.name" />
            </div>
            <div class="flex flex-col gap-1.5">
                <Label for="status">Status</Label>
                <select id="status" v-model="form.status" :class="controlClass">
                    <option
                        v-for="s in statuses"
                        :key="s.value"
                        :value="s.value"
                    >
                        {{ s.label }}
                    </option>
                </select>
                <InputError :message="form.errors.status" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-1.5">
                <Label for="city">Oraș</Label>
                <select
                    id="city"
                    v-model.number="form.city_id"
                    :class="controlClass"
                >
                    <option v-for="c in cities" :key="c.id" :value="c.id">
                        {{ c.name }}
                    </option>
                </select>
                <InputError :message="form.errors.city_id" />
            </div>
            <div class="flex flex-col gap-1.5">
                <Label for="address">Adresă</Label>
                <Input id="address" v-model="form.address" />
                <InputError :message="form.errors.address" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-1.5">
                <Label for="lat">Latitudine</Label>
                <Input
                    id="lat"
                    v-model="form.lat"
                    type="number"
                    step="0.0000001"
                />
                <InputError :message="form.errors.lat" />
            </div>
            <div class="flex flex-col gap-1.5">
                <Label for="lng">Longitudine</Label>
                <Input
                    id="lng"
                    v-model="form.lng"
                    type="number"
                    step="0.0000001"
                />
                <InputError :message="form.errors.lng" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="flex flex-col gap-1.5">
                <Label for="phone">Telefon</Label>
                <Input id="phone" v-model="form.phone" />
            </div>
            <div class="flex flex-col gap-1.5">
                <Label for="email">Email</Label>
                <Input id="email" v-model="form.email" type="email" />
                <InputError :message="form.errors.email" />
            </div>
            <div class="flex flex-col gap-1.5">
                <Label for="website">Site</Label>
                <Input id="website" v-model="form.website" type="url" />
                <InputError :message="form.errors.website" />
            </div>
        </div>

        <div class="flex flex-col gap-1.5">
            <Label for="description">Descriere</Label>
            <textarea
                id="description"
                v-model="form.description"
                rows="4"
                :class="controlClass"
            ></textarea>
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
        </fieldset>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">Salvează</Button>
            <a
                href="/admin/clinici"
                class="text-sm text-muted-foreground hover:underline"
                >Înapoi</a
            >
        </div>
    </form>
</template>
