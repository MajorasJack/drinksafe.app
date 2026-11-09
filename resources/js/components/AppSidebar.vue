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
import { computed, watch } from 'vue';
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
    useSidebar,
} from '@/components/ui/sidebar';
import { home } from '@/routes';
import { dashboard } from '@/routes/admin';
import type { NavItem } from '@/types';

const page = usePage();
const { setOpenMobile } = useSidebar();

// the layout persists between pages, so close the mobile sheet once a link has been followed
watch(
    () => page.url,
    () => setOpenMobile(false),
);

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
                        <Link
                            :href="
                                page.props.auth.user?.is_admin
                                    ? dashboard()
                                    : home()
                            "
                        >
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain v-if="mainNavItems.length > 0" :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
