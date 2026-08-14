<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pager from '@/components/admin/Pager.vue';
import { Input } from '@/components/ui/input';

interface UserRow {
    id: number;
    name: string;
    email: string;
    role: string;
    owned_clinics: number;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

const props = defineProps<{
    users: Paginated<UserRow>;
    filters: { q: string };
    roles: { value: string; label: string }[];
    canAssign: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administrare', href: '/admin' },
            { title: 'Utilizatori', href: '/admin/utilizatori' },
        ],
    },
});

const search = ref(props.filters.q);
const selectClass =
    'border-input bg-background h-8 rounded-md border px-2 text-sm shadow-xs outline-none';

let debounce: ReturnType<typeof setTimeout> | undefined;
watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(
            '/admin/utilizatori',
            { q: search.value },
            { preserveState: true, replace: true },
        );
    }, 300);
});

function setRole(id: number, role: string): void {
    router.patch(
        `/admin/utilizatori/${id}/rol`,
        { role },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Utilizatori" />

    <div class="flex flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold tracking-tight">Utilizatori</h1>

        <Input
            v-model="search"
            type="search"
            placeholder="Caută nume sau email…"
            class="max-w-xs"
        />

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-2">Nume</th>
                        <th class="px-4 py-2">Email</th>
                        <th class="px-4 py-2">Clinici</th>
                        <th class="px-4 py-2">Rol</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="user in users.data"
                        :key="user.id"
                        class="border-t"
                    >
                        <td class="px-4 py-2 font-medium">{{ user.name }}</td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ user.email }}
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ user.owned_clinics }}
                        </td>
                        <td class="px-4 py-2">
                            <select
                                v-if="canAssign"
                                :value="user.role"
                                :class="selectClass"
                                @change="
                                    setRole(
                                        user.id,
                                        ($event.target as HTMLSelectElement)
                                            .value,
                                    )
                                "
                            >
                                <option
                                    v-for="role in roles"
                                    :key="role.value"
                                    :value="role.value"
                                >
                                    {{ role.label }}
                                </option>
                            </select>
                            <span v-else class="text-muted-foreground">{{
                                user.role
                            }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pager
            :prev-url="users.prev_page_url"
            :next-url="users.next_page_url"
            :current="users.current_page"
            :last="users.last_page"
        />
    </div>
</template>
