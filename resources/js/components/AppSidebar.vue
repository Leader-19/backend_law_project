<script setup lang="ts">
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
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Calendar, FileText, LayoutGrid, Phone, MessagesSquare, BriefcaseBusiness, Notebook, Presentation, Users, Download, ShieldCheck, ScrollText } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { can } from '@/lib/can';

const hasAnyPermission = (permissions: string[]) => permissions.some((permission) => can(permission));

const hasUserPermission = hasAnyPermission(['users.view', 'users.create', 'users.edit', 'users.delete']);
const hasRolePermission = hasAnyPermission(['roles.view', 'roles.create', 'roles.edit', 'roles.delete']);
const hasCategoryPermission = hasAnyPermission(['category.view', 'category.create', 'category.edit', 'category.delete']);
const hasDocumentPermission = hasAnyPermission(['document.view', 'document.create', 'document.edit', 'document.delete']);

const mainNavItems: NavItem[] = [
    {
        title: 'តារាង គ្របគ្រង​ ទិន្នន័យ',
        href: dashboard(),
        icon: LayoutGrid,
        items: undefined
    },

    ...(hasUserPermission ? [{
        title: 'អ្នកប្រើប្រាស់',
        href: '/users',
        icon: Users,
        items: undefined
    }] : []),

    ...(hasRolePermission ? [{
        title: 'តួនាទី',
        href: '/roles',
        icon: Notebook,
        items: undefined
    }] : []),
    ...(hasRolePermission ? [{
        title: 'ការអនុញ្ញាត',
        href: '/permissions',
        icon: ShieldCheck,
        items: undefined
    }] : []),

    ...(hasCategoryPermission ? [{
        title: 'ប្រភេទ',
        href: '/categories',
        icon: Calendar,
        items: undefined
    }] : []),
    ...(hasDocumentPermission ? [{
        title: 'គ្របគ្រងប្រភេទ',
        href: '/category-management',
        icon: Calendar,
        items: undefined
    }] : []),
    ...(hasDocumentPermission ? [{
        title: 'ឯកសារ',
        href: '/documents',
        icon: FileText,
        items: undefined
    }] : []),
    {
        title: 'Backup',
        href: '/backup/download',
        icon: Download,
        items: undefined,
        external: true,
    },
    {
        title: 'Log Viewer',
        href: '/log-viewer',
        icon: ScrollText,
        items: undefined,
    },
    // {
    //     title: 'Final Slides',
    //     href: '/final-slides',
    //     icon: Presentation,
    //     items: undefined
    // },
    // {
    //     title: 'Contact Supervisor',
    //     href: '/contact-supervisors',
    //     icon: Phone,
    //     items: undefined
    // },
    //  {
    //     title: 'Company Interviews',
    //     href: '/company-interviews',
    //     icon: MessagesSquare,
    //     items: undefined
    // },
    //  {
    //     title: 'Company Internship',
    //     href: '/internships',
    //     icon: BriefcaseBusiness,
    //     items: undefined
    // },
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
