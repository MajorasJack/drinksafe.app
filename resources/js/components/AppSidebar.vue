<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ClipboardCheck,
    FileText,
    LayoutGrid,
    MapPin,
    QrCode,
    Table,
} from 'lucide-vue-next';
import type { LucideIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
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
import { dashboard } from '@/routes/admin';
import type { NavItem } from '@/types';

const page = usePage();

const adminNavIcons: Record<string, LucideIcon> = {
    'admin.dashboard': LayoutGrid,
    'admin.reports.index': ClipboardCheck,
    'admin.reports.search': FileText,
    'admin.venues.index': MapPin,
    'admin.partners.index': QrCode,
    'admin.coverage': Table,
};

const mainNavItems = computed<NavItem[]>(() =>
    page.props.admin.nav.map((item) => ({
        title: item.title,
        href: item.href,
        icon: adminNavIcons[item.name],
    })),
);
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
