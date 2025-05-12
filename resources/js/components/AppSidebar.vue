<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Calendar, ChartBar, ClipboardCheck } from 'lucide-vue-next';
import { computed } from 'vue';
import { useAuth } from '@/composables/useAuth';
import AppLogo from './AppLogo.vue';

const { user } = useAuth();
const isAdmin = computed(() => user.value?.role === 'admin');
const isTrainer = computed(() => user.value?.role === 'trainer');

const mainNavItems = computed<NavItem[]>(() => {
    const items = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'Training Calendar',
            href: '/training-calendar',
            icon: Calendar,
        },
    ];

    if (isAdmin.value) {
        items.push({
            title: 'User Management',
            href: '/users',
            icon: Folder,
        });
    }

    if (isAdmin.value || isTrainer.value) {
        items.push({
            title: 'Attendance Tracker',
            href: '/training/attendance',
            icon: ClipboardCheck,
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
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
