<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    FolderTree,
    Users,
    FileText,
    BookOpen,
    Activity,
    ArrowUpRight,
    Sparkles,
    ShieldCheck,
    CreditCard,
    Plus,
    Clock,
    CheckCircle2,
    AlertCircle,
    Search,
    X,
    AlertTriangle,
    Info,
    ClipboardList,
    Award,
    UserCheck,
    MessageSquare,
    Trophy
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

interface DashboardProps {
    stats?: {
        total_categories: number;
        total_users: number;
        total_documents: number;
        total_text_contents: number;
        total_activities: number;
        pending_approvals: number;
        total_quizzes: number;
        total_quiz_attempts: number;
        total_certificates: number;
        open_messages: number;
    };
    recent_activity?: Array<{
        id: number;
        action: string;
        description: string;
        causer_name: string;
        created_at: string;
    }>;
    recent_documents?: Array<{
        id: number;
        doc_name: string;
        category_title: string;
        user_name: string;
        created_at: string;
    }>;
    categories?: Array<{
        id: number;
        title: string;
        description: string | null;
        documents_count: number;
        permission: string;
    }>;
    subscription?: {
        id: number;
        status: string;
        starts_at: string;
        ends_at: string;
        plan: {
            id: number;
            name: string;
            slug: string;
            price: number;
            currency: string;
            max_categories: number | null;
            max_documents: number | null;
            max_text_contents: number | null;
            max_storage_mb: number | null;
        } | null;
    };
}

const props = withDefaults(defineProps<DashboardProps & { activity_search?: string }>(), {
    stats: () => ({
        total_categories: 0,
        total_users: 0,
        total_documents: 0,
        total_text_contents: 0,
        total_activities: 0,
        pending_approvals: 0,
        total_quizzes: 0,
        total_quiz_attempts: 0,
        total_certificates: 0,
        open_messages: 0,
    }),
    recent_activity: () => [],
    recent_documents: () => [],
    categories: () => [],
    subscription: null,
    activity_search: '',
});

const activitySearch = ref(props.activity_search ?? '');

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
function onActivitySearchInput() {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const params: Record<string, string> = {};
        if (activitySearch.value.trim()) params.activity_search = activitySearch.value.trim();
        router.get('/dashboard', params, { preserveState: true, replace: true });
    }, 400);
}

function clearActivitySearch() {
    activitySearch.value = '';
    router.get('/dashboard', {}, { preserveState: true, replace: true });
}

function getActionClass(action: string) {
    const classes: Record<string, string> = {
        created: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        updated: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
        deleted: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
        issue: 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300',
        health_check: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    };
    return classes[action] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
}

function getActionIcon(action: string) {
    if (action === 'issue') return AlertTriangle;
    if (action === 'health_check') return CheckCircle2;
    return Activity;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const page = usePage();

const user = computed(() => page.props.auth.user as any);

const isAdmin = computed(() => {
    return user.value?.roles?.some((role: any) => role.name === 'Admin' || role.name === 'admin') ?? false;
});

const hasData = computed(() => {
    return (props.stats.total_categories > 0 || props.stats.total_documents > 0 || props.stats.total_activities > 0);
});

const subscriptionPlan = computed(() => props.subscription?.plan)
const isSubscriptionActive = computed(() => props.subscription?.status === 'active')

const permissionBadgeClass: Record<string, string> = {
    manage: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    edit: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    create: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
    view: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    delete: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 sm:p-8 max-w-7xl mx-auto w-full">
            <!-- Hero Welcome Card -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/10">
                <div class="absolute -right-10 -bottom-10 h-64 w-64 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                <div class="absolute right-1/3 -top-10 h-48 w-48 rounded-full bg-purple-400/20 blur-xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur-md border border-white/20 mb-3">
                            <Sparkles class="h-3.5 w-3.5 text-amber-300" />
                            <span>{{ isAdmin ? 'Super Admin System Portal' : 'User Control Center' }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                            Welcome back, {{ user?.name || 'Administrator' }}! 👋
                        </h1>
                        <p class="mt-2 text-sm text-blue-100/90 max-w-2xl leading-relaxed">
                            {{ isAdmin
                                ? 'Full administrative access active. All roles and categories auto-assigned. Manage users, categories, documents, and multi-currency subscriptions seamlessly.'
                                : 'Access your assigned category dashboards, manage documents, and review recent activity.'
                            }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3 flex-wrap">
                        <Link v-if="isAdmin" href="/users" class="inline-flex items-center gap-2 rounded-xl bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 px-4 py-2.5 text-xs font-semibold text-white transition-all shadow-sm">
                            <Users class="h-4 w-4" />
                            Manage Users
                        </Link>
                        <Link href="/categories" class="inline-flex items-center gap-2 rounded-xl bg-white text-blue-600 hover:bg-blue-50 px-4 py-2.5 text-xs font-bold transition-all shadow-md">
                            <Plus class="h-4 w-4" />
                            Explore Categories
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Categories Stat Card -->
                <div class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Categories</p>
                            <p class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                {{ stats.total_categories }}
                            </p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                            <FolderTree class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-slate-500 dark:text-slate-400">
                        <span class="text-emerald-500 font-semibold flex items-center gap-1">
                            <CheckCircle2 class="h-3.5 w-3.5" /> Active
                        </span>
                        <span class="mx-2">•</span>
                        <span>Category tree management</span>
                    </div>
                </div>

                <!-- Users Stat Card (Admin) -->
                <div v-if="isAdmin" class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Users</p>
                            <p class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                {{ stats.total_users }}
                            </p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                            <Users class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-slate-500 dark:text-slate-400">
                        <span class="text-indigo-500 font-semibold flex items-center gap-1">
                            <ShieldCheck class="h-3.5 w-3.5" /> All Roles Auto-Assigned
                        </span>
                    </div>
                </div>

                <!-- Documents Stat Card -->
                <div class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ isAdmin ? 'Total Items' : 'My Assigned Items' }}</p>
                            <p class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                                {{ stats.total_documents }}
                            </p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400">
                            <FileText class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-slate-500 dark:text-slate-400">
                        <span>Items & category uploads</span>
                    </div>
                </div>

                <!-- Text Contents Stat Card -->
                <div class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Text Contents</p>
                            <p class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                {{ stats.total_text_contents }}
                            </p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400">
                            <BookOpen class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-slate-500 dark:text-slate-400">
                        <span>Book text content entries</span>
                    </div>
                </div>

                <!-- Pending Approvals Stat Card (Admin) -->
                <div v-if="isAdmin && stats.pending_approvals > 0" class="group relative overflow-hidden rounded-2xl border border-yellow-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Pending Approvals</p>
                            <p class="mt-2 text-3xl font-extrabold text-yellow-600 dark:text-yellow-400">
                                {{ stats.pending_approvals }}
                            </p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-500/10 text-yellow-600 dark:bg-yellow-500/20 dark:text-yellow-400">
                            <UserCheck class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <Link href="/user-approvals?status=pending" class="text-xs font-semibold text-yellow-600 hover:text-yellow-700 flex items-center gap-1">
                            Review Now <ArrowUpRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>

                <!-- Quizzes Stat Card (Admin) -->
                <div v-if="isAdmin" class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Quizzes</p>
                            <p class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                                {{ stats.total_quizzes }}
                            </p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-500/10 text-cyan-600 dark:bg-cyan-500/20 dark:text-cyan-400">
                            <ClipboardList class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-slate-500 dark:text-slate-400">
                        <span>{{ stats.total_quiz_attempts }} total attempts</span>
                    </div>
                </div>

                <!-- Certificates Stat Card (Admin) -->
                <div v-if="isAdmin" class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Certificates</p>
                            <p class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white group-hover:text-yellow-600 dark:group-hover:text-yellow-400 transition-colors">
                                {{ stats.total_certificates }}
                            </p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-500/10 text-yellow-600 dark:bg-yellow-500/20 dark:text-yellow-400">
                            <Award class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <Link href="/certificate-management" class="text-xs font-semibold text-yellow-600 hover:text-yellow-700 flex items-center gap-1">
                            Manage <ArrowUpRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>

                <!-- Open Messages Stat Card (Admin) -->
                <div v-if="isAdmin && stats.open_messages > 0" class="group relative overflow-hidden rounded-2xl border border-orange-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Open Messages</p>
                            <p class="mt-2 text-3xl font-extrabold text-orange-600 dark:text-orange-400">
                                {{ stats.open_messages }}
                            </p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-500/10 text-orange-600 dark:bg-orange-500/20 dark:text-orange-400">
                            <MessageSquare class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <Link href="/contact-messages?status=open" class="text-xs font-semibold text-orange-600 hover:text-orange-700 flex items-center gap-1">
                            Reply Now <ArrowUpRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>

                <!-- Activity Log Stat Card -->
                <div class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">System Logs</p>
                            <p class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                                {{ stats.total_activities }}
                            </p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                            <Activity class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-slate-500 dark:text-slate-400">
                        <span class="flex items-center gap-1"><Clock class="h-3.5 w-3.5" /> Real-time action audit</span>
                    </div>
                </div>
            </div>

            <!-- Subscription Plan Card (non-admin only) -->
            <div v-if="!isAdmin" class="rounded-3xl border border-slate-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white">
                            <CreditCard class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                {{ subscriptionPlan ? subscriptionPlan.name : 'No Active Plan' }}
                            </h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ isSubscriptionActive ? 'Active subscription' : 'Choose a plan to get started' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div v-if="subscriptionPlan" class="text-right">
                            <p class="text-xs text-slate-500 dark:text-slate-400">Limits</p>
                            <p class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                {{ subscriptionPlan.max_categories || '∞' }} categories • {{ subscriptionPlan.max_documents || '∞' }} docs • {{ subscriptionPlan.max_text_contents || '∞' }} texts
                            </p>
                        </div>
                        <Link
                            href="/subscription-plans"
                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-blue-700 transition-colors shadow-sm"
                        >
                            {{ subscriptionPlan ? 'Change Plan' : 'View Plans' }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- My Assigned Categories (non-admin only) -->
            <div v-if="!isAdmin && categories.length" class="rounded-3xl border border-slate-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800 mb-4">
                    <div class="flex items-center gap-2">
                        <FolderTree class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">My Assigned Categories</h2>
                    </div>
                    <Link href="/categories" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                        View All <ArrowUpRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="category in categories"
                        :key="category.id"
                        :href="`/categories/${category.id}/dashboard`"
                        class="flex items-center gap-3 p-4 rounded-2xl bg-slate-50/70 dark:bg-slate-800/50 hover:bg-slate-100/80 dark:hover:bg-slate-800 transition-colors group"
                    >
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 flex-shrink-0">
                            <FolderTree class="h-5 w-5" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ category.title }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                {{ category.documents_count }} documents •
                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold ml-1"
                                    :class="permissionBadgeClass[category.permission] || permissionBadgeClass.view">
                                    {{ category.permission }}
                                </span>
                            </p>
                        </div>
                        <ArrowUpRight class="h-4 w-4 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-white transition-colors flex-shrink-0" />
                    </Link>
                </div>
            </div>

            <!-- Two-Column Grid for Recent Activity & Documents -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Recent Activity Panel -->
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800 mb-4">
                        <div class="flex items-center gap-2">
                            <Activity class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">Recent Activity Log</h2>
                        </div>
                        <Link href="/activity-logs" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                            View All <ArrowUpRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>

                    <!-- Activity Search -->
                    <div class="mb-4">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <input
                                v-model="activitySearch"
                                @input="onActivitySearchInput"
                                type="text"
                                placeholder="Search activities..."
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-9 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                            <button
                                v-if="activitySearch"
                                @click="clearActivitySearch"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="recent_activity.length" class="space-y-3">
                        <div v-for="activity in recent_activity" :key="activity.id"
                            class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-slate-50/70 dark:bg-slate-800/50 hover:bg-slate-100/80 dark:hover:bg-slate-800 transition-colors">
                            <div :class="['flex h-9 w-9 items-center justify-center rounded-xl font-bold text-xs flex-shrink-0', getActionClass(activity.action)]">
                                <component :is="getActionIcon(activity.action)" class="w-4 h-4" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">
                                        {{ activity.description || activity.action }}
                                    </p>
                                    <span v-if="activity.severity" :class="[ 'inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-bold uppercase', activity.severity === 'critical' ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' : activity.severity === 'warning' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300' ]">
                                        {{ activity.severity }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    by <span class="font-medium text-slate-700 dark:text-slate-300">{{ activity.causer_name }}</span> • {{ activity.created_at }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="!hasData" class="text-center py-10 text-xs text-slate-400">
                        No recent activity logged.
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-10 text-xs text-slate-400 gap-2">
                        <AlertCircle class="h-8 w-8 text-slate-300 dark:text-slate-600" />
                        <p>No recent activity to display.</p>
                    </div>
                </div>

                <!-- Recent Items Panel -->
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800 mb-4">
                        <div class="flex items-center gap-2">
                            <FileText class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">Recent Category Items</h2>
                        </div>
                        <Link href="/documents" class="text-xs font-semibold text-purple-600 hover:text-purple-700 flex items-center gap-1">
                            Manage All <ArrowUpRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>

                    <div v-if="recent_documents.length" class="space-y-3">
                        <div v-for="doc in recent_documents" :key="doc.id"
                            class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-slate-50/70 dark:bg-slate-800/50 hover:bg-slate-100/80 dark:hover:bg-slate-800 transition-colors">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300 font-bold text-xs flex-shrink-0">
                                <FileText class="w-4 h-4" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">
                                    {{ doc.doc_name }}
                                </p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    <span class="font-medium text-purple-600 dark:text-purple-400">{{ doc.category_title }}</span> • by {{ doc.user_name }}
                                </p>
                            </div>
                            <Link :href="`/documents/${doc.id}`"
                                class="flex-shrink-0 rounded-xl p-2 text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-700 hover:text-slate-700 dark:hover:text-white transition-colors">
                                <ArrowUpRight class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                    <div v-else-if="!hasData" class="text-center py-10 text-xs text-slate-400">
                        No category items created yet.
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-10 text-xs text-slate-400 gap-2">
                        <AlertCircle class="h-8 w-8 text-slate-300 dark:text-slate-600" />
                        <p>No recent items to display.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
