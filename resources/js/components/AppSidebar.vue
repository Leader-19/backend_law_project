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
import { Link, usePage } from '@inertiajs/vue3';
import { Calendar, FileText, LayoutGrid, Phone, MessagesSquare, BriefcaseBusiness, Notebook, Presentation, Users, Download, ShieldCheck, ScrollText, Upload, CreditCard, CalendarCheck, UserPlus, Banknote, Activity, FolderTree, CreditCardIcon, BookOpen, BookMarked, Clock, ClipboardList, Trophy, Award, MessageSquare, UserCheck } from '@lucide/vue';
import AppLogo from './AppLogo.vue';
import { can } from '@/lib/can';
import { computed } from 'vue'

const page = usePage()

const hasAnyPermission = (permissions: string[]) => permissions.some((permission) => can(permission));

const hasUserPermission = hasAnyPermission(['users.view', 'users.create', 'users.edit', 'users.delete']);
const hasRolePermission = hasAnyPermission(['roles.view', 'roles.create', 'roles.edit', 'roles.delete']);
const hasCategoryPermission = hasAnyPermission(['category.view', 'category.create', 'category.edit', 'category.delete']);
const hasDocumentPermission = hasAnyPermission(['document.view', 'document.create', 'document.edit', 'document.delete']);

const hasDashboardPermission = can('dashboard.view');
const hasDocumentPermission2 = hasAnyPermission(['document.view', 'document.create', 'document.edit', 'document.delete']);

const hasAdminPermission = hasAnyPermission([
    'users.view', 'users.create', 'users.edit', 'users.delete',
    'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
    'permissions.view',
    'category.view', 'category.create', 'category.edit', 'category.delete',
    'document.view', 'document.create', 'document.edit', 'document.delete',
]);

const sidebarCategories = computed(() => (page.props.sidebarCategories as any[]) || [])

const mainNavItems = computed<NavItem[]>(() => [
    ...(hasDashboardPermission ? [{
        title: 'ផ្ទាំងគ្រប់គ្រង',
        href: dashboard(),
        icon: LayoutGrid,
        items: [
            {
                title: 'ទិដ្ឋភាពទូទៅ',
                href: dashboard(),
                icon: LayoutGrid,
            },
            // ...sidebarCategories.value.map((cat: any) => ({
            //     title: `${cat.title} Dashboard`,
            //     href: `/categories/${cat.id}/dashboard`,
            //     icon: Calendar,
            // })),
        ],
    }] : []),

    ...(hasAdminPermission ? [{
        title: 'ការគ្រប់គ្រង',
        href: '/dashboard',
        icon: BriefcaseBusiness,
        items: [
            ...(hasUserPermission ? [{
                title: 'អ្នកប្រើប្រាស់',
                href: '/users',
                icon: Users,
                items: [
                    {
                        title: 'ទិដ្ឋភាពអ្នកប្រើ',
                        href: '/users',
                        icon: Users,
                    },
                    {
                        title: 'ផ្តល់ការអនុញ្ញាត',
                        href: '/users/categories/assign',
                        icon: CalendarCheck,
                    },
                    {
                        title: 'ការអនុញ្ញាតប្រភេទ',
                        href: '/users/categories',
                        icon: Calendar,
                    },
                    {
                        title: 'Frontend Registrations',
                        href: '/frontend-users',
                        icon: UserPlus,
                    },
                ]
            }] : []),
            ...(hasUserPermission ? [{
                title: 'Assign & Subscribe',
                href: '/frontend-users',
                icon: FolderTree,
                items: [
                    {
                        title: 'Assign Categories to Users',
                        href: '/frontend-users',
                        icon: FolderTree,
                    },
                    {
                        title: 'Subscribe User to Plan',
                        href: '/frontend-users',
                        icon: CreditCardIcon,
                    },
                ]
            }] : []),
            ...(hasRolePermission ? [{
                title: 'តួនាទី',
                href: '/roles',
                icon: Notebook,
            }] : []),
            ...(hasRolePermission ? [{
                title: 'ការអនុញ្ញាត',
                href: '/permissions',
                icon: ShieldCheck,
            }] : []),
            ...(hasCategoryPermission ? [{
                title: 'ប្រភេទ',
                href: '/categories',
                icon: Calendar,
            }] : []),
            ...(hasDocumentPermission ? [{
                title: 'គ្របគ្រងប្រភេទ',
                href: '/category-management',
                icon: Calendar,
            }] : []),
            ...(hasDocumentPermission ? [{
                title: 'ឯកសារ',
                href: '/documents',
                icon: FileText,
            }] : []),
            ...(hasDocumentPermission ? [{
                title: 'អត្ថបទ',
                href: '/text-contents',
                icon: BookOpen,
            }] : []),
            ...(hasDocumentPermission ? [{
                title: 'Import Documents',
                href: '/documents/batch/create',
                icon: Upload,
            }] : []),
            {
                title: 'Subscription Plans',
                href: '/subscription-plans',
                icon: CreditCard,
                items: [
                    { title: 'Manage Plans', href: '/subscription-plans', icon: CreditCard },
                    { title: 'Payments', href: '/payments', icon: Banknote },
                ],
            },
        ],
    }] : []),

    // User-facing items
    {
        title: 'My Library',
        href: '/library',
        icon: BookMarked,
        items: undefined,
    },
    {
        title: 'Reading History',
        href: '/reading-history',
        icon: Clock,
        items: undefined,
    },
    {
        title: 'Quizzes',
        href: '/quizzes',
        icon: ClipboardList,
        items: undefined,
    },
    {
        title: 'Leaderboard',
        href: '/leaderboard',
        icon: Trophy,
        items: undefined,
    },
    {
        title: 'Certificates',
        href: '/certificates',
        icon: Award,
        items: undefined,
    },
    {
        title: 'Contact Admin',
        href: '/contact',
        icon: MessageSquare,
        items: undefined,
    },

    // Admin items
    ...(hasAnyPermission(['users.view', 'users.edit']) ? [{
        title: 'User Approvals',
        href: '/user-approvals',
        icon: UserCheck,
        items: undefined,
    }] : []),
    ...(hasDocumentPermission2 ? [{
        title: 'Quiz Management',
        href: '/quiz-management',
        icon: ClipboardList,
        items: undefined,
    }] : []),
    ...(hasDocumentPermission2 ? [{
        title: 'Certificate Mgmt',
        href: '/certificate-management',
        icon: Award,
        items: undefined,
    }] : []),
    ...(hasAnyPermission(['users.view']) ? [{
        title: 'Contact Messages',
        href: '/contact-messages',
        icon: MessageSquare,
        items: undefined,
    }] : []),

    {
        title: 'Backup',
        href: '/backup',
        icon: Download,
        items: undefined,
    },
    {
        title: 'Activity Logs',
        href: '/activity-logs',
        icon: Activity,
        items: undefined,
    },
]);

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
