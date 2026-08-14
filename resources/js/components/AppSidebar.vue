<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Building2,
    ClipboardCheck,
    FolderGit2,
    LayoutGrid,
    Search,
    ShieldCheck,
    Star,
    Stethoscope,
    UserCheck,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();

const canModerate = computed(() => {
    const role = page.props.auth?.user?.role;

    return role === 'admin' || role === 'moderator';
});

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
        { title: 'Clinicile mele', href: '/cont', icon: Stethoscope },
    ];

    if (canModerate.value) {
        items.push(
            { title: 'Administrare', href: '/admin', icon: ShieldCheck },
            { title: 'Moderare', href: '/admin/moderare', icon: ClipboardCheck },
            { title: 'Clinici', href: '/admin/clinici', icon: Building2 },
            { title: 'Revendicări', href: '/admin/revendicari', icon: UserCheck },
            { title: 'Recenzii', href: '/admin/recenzii', icon: Star },
            { title: 'Utilizatori', href: '/admin/utilizatori', icon: Users },
            { title: 'Unelte SEO', href: '/admin/seo', icon: Search },
        );
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
