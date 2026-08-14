<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';
import type { Appearance } from '@/types';

// Reuses the same composable the dashboard uses, so the light/dark/system choice
// is stored in one place (localStorage + the `appearance` cookie) and stays in
// sync across the public site and the authenticated app.
const { appearance, updateAppearance } = useAppearance();

const order: Appearance[] = ['light', 'dark', 'system'];

const current = computed(() => {
    if (appearance.value === 'light') {
        return { icon: Sun, label: 'Temă: luminoasă' };
    }

    if (appearance.value === 'dark') {
        return { icon: Moon, label: 'Temă: întunecată' };
    }

    return { icon: Monitor, label: 'Temă: sistem' };
});

function next(): void {
    const index = order.indexOf(appearance.value);
    updateAppearance(order[(index + 1) % order.length]);
}
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        :aria-label="current.label"
        :title="current.label"
        @click="next"
    >
        <component :is="current.icon" class="size-5" />
    </Button>
</template>
