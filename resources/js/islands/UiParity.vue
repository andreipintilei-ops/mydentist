<script setup lang="ts">
/**
 * Dev-only island. Renders the real shadcn-vue components so they can be
 * compared side by side with their Blade mirrors in resources/views/components/ui.
 * If these two columns ever stop matching, the mirrors have drifted.
 */
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';

const buttonVariants = [
    'default',
    'destructive',
    'outline',
    'secondary',
    'ghost',
    'link',
] as const;
const buttonSizes = ['default', 'sm', 'lg'] as const;
const badgeVariants = [
    'default',
    'secondary',
    'destructive',
    'outline',
] as const;
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center gap-2">
            <Button
                v-for="variant in buttonVariants"
                :key="variant"
                :variant="variant"
            >
                {{ variant }}
            </Button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Button v-for="size in buttonSizes" :key="size" :size="size">
                size: {{ size }}
            </Button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Badge
                v-for="variant in badgeVariants"
                :key="variant"
                :variant="variant"
            >
                {{ variant }}
            </Badge>
        </div>

        <Separator />

        <Input placeholder="Caută o clinică…" />

        <Card>
            <CardHeader>
                <CardTitle>Clinica Stomatologică NovaDent</CardTitle>
                <CardDescription>Strada Păcurari 12, Iași</CardDescription>
            </CardHeader>
            <CardContent>
                <p class="text-sm">
                    Implant dentar, ortodonție, albire dentară.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
