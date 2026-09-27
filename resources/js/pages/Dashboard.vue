<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    FolderTree,
    Users,
    FileText,
    BookOpen,
    Activity,
    ArrowUpRight,
    ShieldCheck,
    CreditCard,
    Plus,
    Clock,
    CheckCircle2,
    AlertCircle,
    Search,
    X,
    AlertTriangle,
    ClipboardList,
    Award,
    UserCheck,
    MessageSquare,
    Filter,
    Check,
    ChevronDown,
} from '@lucide/vue';
import { computed, ref, onMounted, onUnmounted } from 'vue';
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
        severity?: string;
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
        permission?: string;
        parent_id?: number | null;
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
    document_usage?: {
        used: number;
        limit: number | null;
        remaining: number | null;
        percentage: number;
        at_limit: boolean;
    } | null;
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
    document_usage: null,
    activity_search: '',
});

const activitySearch = ref(props.activity_search ?? '');

// Category search + sort
const categorySearch = ref('');
const categorySort = ref<'title_asc' | 'title_desc' | 'docs_desc' | 'docs_asc'>('title_asc');

// Excel-style "filter by name" dropdown. A category id in this set is
// hidden from the list; empty set (the default) means nothing is hidden.
const hiddenCategoryIds = ref<Set<number>>(new Set());
const categoryFilterOpen = ref(false);
const categoryFilterListSearch = ref('');
const categoryFilterRef = ref<HTMLElement | null>(null);

// The full, alphabetical list of names shown inside the filter dropdown,
// independent of the main search/sort applied to the grid itself.
const categoryFilterOptions = computed(() => {
    const list = [...(props.categories ?? [])].sort((a, b) => a.title.localeCompare(b.title));
    const q = categoryFilterListSearch.value.trim().toLowerCase();
    if (!q) return list;
    return list.filter((cat) => cat.title.toLowerCase().includes(q));
});

const isCategoryFilterActive = computed(() => hiddenCategoryIds.value.size > 0);

function isCategoryChecked(id: number) {
    return !hiddenCategoryIds.value.has(id);
}

function toggleCategoryChecked(id: number) {
    const next = new Set(hiddenCategoryIds.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    hiddenCategoryIds.value = next;
}

function selectAllCategoryFilters() {
    hiddenCategoryIds.value = new Set();
}

function clearAllCategoryFilters() {
    hiddenCategoryIds.value = new Set((props.categories ?? []).map((c) => c.id));
}

function toggleCategoryFilterOpen() {
    categoryFilterOpen.value = !categoryFilterOpen.value;
}

function handleClickOutsideFilter(event: MouseEvent) {
    if (categoryFilterRef.value && !categoryFilterRef.value.contains(event.target as Node)) {
        categoryFilterOpen.value = false;
    }
}

onMounted(() => document.addEventListener('click', handleClickOutsideFilter));
onUnmounted(() => document.removeEventListener('click', handleClickOutsideFilter));

const sortedCategories = computed(() => {
    let list = [...(props.categories ?? [])].filter((cat) => !hiddenCategoryIds.value.has(cat.id));

    const q = categorySearch.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (cat) =>
                cat.title.toLowerCase().includes(q) ||
                (cat.description && cat.description.toLowerCase().includes(q))
        );
    }

    switch (categorySort.value) {
        case 'title_desc':
            return list.sort((a, b) => b.title.localeCompare(a.title));
        case 'docs_desc':
            return list.sort((a, b) => (b.documents_count ?? 0) - (a.documents_count ?? 0));
        case 'docs_asc':
            return list.sort((a, b) => (a.documents_count ?? 0) - (b.documents_count ?? 0));
        case 'title_asc':
        default:
            return list.sort((a, b) => a.title.localeCompare(b.title));
    }
});

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
    return props.stats.total_categories > 0 || props.stats.total_documents > 0 || props.stats.total_activities > 0;
});

const subscriptionPlan = computed(() => props.subscription?.plan);
const isSubscriptionActive = computed(() => props.subscription?.status === 'active');

const permissionBadgeClass: Record<string, string> = {
    manage: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    edit: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    create: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
    view: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    delete: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
};

// Items that need action right now, surfaced once instead of hidden among stat tiles.
const attentionItems = computed(() => {
    const items: Array<{ key: string; label: string; count: number; href: string; cta: string; icon: any }> = [];
    if (isAdmin.value && props.stats.pending_approvals > 0) {
        items.push({
            key: 'approvals',
            label: 'User accounts waiting for approval',
            count: props.stats.pending_approvals,
            href: '/user-approvals?status=pending',
            cta: 'Review',
            icon: UserCheck,
        });
    }
    if (isAdmin.value && props.stats.open_messages > 0) {
        items.push({
            key: 'messages',
            label: 'Open contact messages',
            count: props.stats.open_messages,
            href: '/contact-messages?status=open',
            cta: 'Reply',
            icon: MessageSquare,
        });
    }
    return items;
});

// Neutral overview tiles. Admin-only ones are filtered out for non-admins.
const overviewStats = computed(() => {
    const tiles = [
        { key: 'categories', label: 'Categories', value: props.stats.total_categories, icon: FolderTree, adminOnly: false, note: 'Category tree' },
        { key: 'users', label: 'Users', value: props.stats.total_users, icon: Users, adminOnly: true, note: 'All roles assigned' },
        {
            key: 'documents',
            label: isAdmin.value ? 'Items' : 'My items',
            value: props.stats.total_documents,
            icon: FileText,
            adminOnly: false,
            note: 'Category uploads',
        },
        { key: 'text', label: 'Text contents', value: props.stats.total_text_contents, icon: BookOpen, adminOnly: false, note: 'Book text entries' },
        { key: 'quizzes', label: 'Quizzes', value: props.stats.total_quizzes, icon: ClipboardList, adminOnly: true, note: `${props.stats.total_quiz_attempts} attempts` },
        { key: 'certificates', label: 'Certificates', value: props.stats.total_certificates, icon: Award, adminOnly: true, note: 'Manage', href: '/certificate-management' },
        { key: 'logs', label: 'System logs', value: props.stats.total_activities, icon: Activity, adminOnly: false, note: 'Action audit' },
    ];
    return tiles.filter((t) => !t.adminOnly || isAdmin.value);
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 sm:p-8 max-w-8xl mx-auto w-full">
            <!-- Welcome header -->
            <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        {{ isAdmin ? 'Admin portal' : 'Your dashboard' }}
                    </p>
                    <h1 class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">
                        Welcome back, {{ user?.name || 'there' }}
                    </h1>
                    <p class="mt-1 max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                        {{ isAdmin
                            ? 'Manage users, categories, documents, and subscriptions from one place.'
                            : 'Here are your assigned categories, recent items, and activity.'
                        }}
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <Link
                        v-if="isAdmin"
                        href="/users"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        <Users class="h-4 w-4" />
                        Manage users
                    </Link>
                    <Link
                        href="/categories"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200"
                    >
                        <Plus class="h-4 w-4" />
                        Explore categories
                    </Link>
                </div>
            </div>

            <!-- Needs attention -->
            <div v-if="attentionItems.length" class="space-y-2">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Needs attention</h2>
                <Link
                    v-for="item in attentionItems"
                    :key="item.key"
                    :href="item.href"
                    class="flex items-center gap-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5 transition-colors hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/40 dark:hover:bg-amber-950/70"
                >
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300">
                        <component :is="item.icon" class="h-4.5 w-4.5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-amber-900 dark:text-amber-100">
                            <span class="font-bold">{{ item.count }}</span> {{ item.label }}
                        </p>
                    </div>
                    <span class="flex shrink-0 items-center gap-1 text-sm font-semibold text-amber-700 dark:text-amber-300">
                        {{ item.cta }} <ArrowUpRight class="h-3.5 w-3.5" />
                    </span>
                </Link>
            </div>

            <!-- Overview stats -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <component
                    :is="stat.href ? Link : 'div'"
                    v-for="stat in overviewStats"
                    :key="stat.key"
                    :href="stat.href"
                    class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ stat.label }}</p>
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                            <component :is="stat.icon" class="h-4.5 w-4.5" />
                        </div>
                    </div>
                    <p class="mt-3 text-2xl font-semibold text-slate-900 dark:text-white">{{ stat.value }}</p>
                    <p class="mt-1 flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ stat.note }}
                        <ArrowUpRight v-if="stat.href" class="h-3 w-3" />
                    </p>
                </component>
            </div>

            <!-- Categories (shared block for admin/non-admin) -->
            <div v-if="categories.length" class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-4 flex flex-col gap-3 border-b border-slate-100 pb-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2">
                        <FolderTree class="h-5 w-5 text-slate-400" />
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">
                            {{ isAdmin ? 'All categories' : 'My assigned categories' }}
                        </h2>
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            ({{ sortedCategories.length }}{{ categorySearch ? ` of ${categories.length}` : '' }})
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="categorySearch"
                                type="text"
                                placeholder="Search categories"
                                class="w-40 rounded-lg border border-slate-200 bg-white py-1.5 pl-8 pr-8 text-xs outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-white dark:focus:ring-white sm:w-52"
                            />
                            <button
                                v-if="categorySearch"
                                @click="categorySearch = ''"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                            >
                                <X class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <select
                            v-model="categorySort"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        >
                            <option value="title_asc">Name A–Z</option>
                            <option value="title_desc">Name Z–A</option>
                            <option value="docs_desc">Most items</option>
                            <option value="docs_asc">Fewest items</option>
                        </select>

                        <!-- Excel-style checkbox filter by category name -->
                        <div ref="categoryFilterRef" class="relative">
                            <button
                                type="button"
                                @click="toggleCategoryFilterOpen"
                                class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors"
                                :class="isCategoryFilterActive
                                    ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200'"
                            >
                                <Filter class="h-3.5 w-3.5" />
                                Filter
                                <span
                                    v-if="isCategoryFilterActive"
                                    class="rounded-full px-1.5 text-[10px] font-bold"
                                    :class="isCategoryFilterActive ? 'bg-white/20' : ''"
                                >
                                    {{ categoryFilterOptions.length - hiddenCategoryIds.size }}
                                </span>
                                <ChevronDown class="h-3.5 w-3.5" />
                            </button>

                            <div
                                v-if="categoryFilterOpen"
                                class="absolute right-0 z-20 mt-2 w-64 rounded-lg border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900"
                            >
                                <div class="relative mb-2">
                                    <Search class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                    <input
                                        v-model="categoryFilterListSearch"
                                        type="text"
                                        placeholder="Search names"
                                        class="w-full rounded-md border border-slate-200 bg-slate-50 py-1.5 pl-8 pr-2 text-xs outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                    />
                                </div>

                                <div class="mb-1 flex items-center justify-between px-1">
                                    <button type="button" @click="selectAllCategoryFilters" class="text-[11px] font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">
                                        Select all
                                    </button>
                                    <button type="button" @click="clearAllCategoryFilters" class="text-[11px] font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">
                                        Clear all
                                    </button>
                                </div>

                                <div class="max-h-56 overflow-y-auto border-t border-slate-100 pt-1 dark:border-slate-800">
                                    <label
                                        v-for="cat in categoryFilterOptions"
                                        :key="cat.id"
                                        class="flex cursor-pointer items-center gap-2 rounded-md px-1.5 py-1.5 text-xs text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                                    >
                                        <span
                                            class="flex h-4 w-4 shrink-0 items-center justify-center rounded border"
                                            :class="isCategoryChecked(cat.id)
                                                ? 'border-slate-900 bg-slate-900 dark:border-white dark:bg-white'
                                                : 'border-slate-300 dark:border-slate-600'"
                                        >
                                            <Check v-if="isCategoryChecked(cat.id)" class="h-3 w-3 text-white dark:text-slate-900" />
                                        </span>
                                        <input
                                            type="checkbox"
                                            :checked="isCategoryChecked(cat.id)"
                                            @change="toggleCategoryChecked(cat.id)"
                                            class="sr-only"
                                        />
                                        <span class="truncate">{{ cat.title }}</span>
                                    </label>
                                    <p v-if="!categoryFilterOptions.length" class="px-1.5 py-3 text-center text-xs text-slate-400">
                                        No matching names.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <Link href="/categories" class="flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">
                            {{ isAdmin ? 'Manage all' : 'View all' }} <ArrowUpRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>

                <div v-if="sortedCategories.length === 0" class="py-12 text-center">
                    <AlertCircle class="mx-auto mb-2 h-8 w-8 text-slate-300 dark:text-slate-600" />
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ isCategoryFilterActive && !categorySearch
                            ? 'No categories are checked in the filter.'
                            : `No categories found matching "${categorySearch}"` }}
                    </p>
                    <button
                        v-if="categorySearch"
                        @click="categorySearch = ''"
                        class="mt-3 text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white"
                    >
                        Clear search
                    </button>
                    <button
                        v-else-if="isCategoryFilterActive"
                        @click="selectAllCategoryFilters"
                        class="mt-3 text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white"
                    >
                        Reset filter
                    </button>
                </div>

                <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="category in sortedCategories"
                        :key="category.id"
                        :href="`/categories/${category.id}/dashboard`"
                        class="group flex items-center gap-3 rounded-xl border border-slate-200 p-4 transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-slate-800 dark:hover:border-slate-700 dark:hover:bg-slate-800/60"
                    >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                            <FolderTree class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ category.title }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <template v-if="isAdmin">
                                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ category.documents_count }}</span> items
                                    <span v-if="category.parent_id" class="ml-1 text-slate-400">· sub-category</span>
                                </template>
                                <template v-else>
                                    {{ category.documents_count }} documents ·
                                    <span
                                        class="ml-1 inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                        :class="permissionBadgeClass[category.permission || 'view'] || permissionBadgeClass.view"
                                    >
                                        {{ category.permission || 'view' }}
                                    </span>
                                </template>
                            </p>
                            <p v-if="isAdmin && category.description" class="mt-0.5 truncate text-xs text-slate-400">{{ category.description }}</p>
                        </div>
                        <ArrowUpRight class="h-4 w-4 shrink-0 text-slate-300 transition-colors group-hover:text-slate-500 dark:group-hover:text-slate-300" />
                    </Link>
                </div>
            </div>

            <!-- Subscription (non-admin only) -->
            <div v-if="!isAdmin" class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-white dark:bg-white dark:text-slate-900">
                            <CreditCard class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                                {{ subscriptionPlan ? subscriptionPlan.name : 'No active plan' }}
                            </h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ isSubscriptionActive ? 'Active subscription' : 'Free plan included' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <div v-if="document_usage" class="hidden text-right sm:block">
                            <p class="text-xs text-slate-500 dark:text-slate-400">Document usage</p>
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                {{ document_usage.used }} / {{ document_usage.limit ?? '∞' }}
                            </p>
                        </div>
                        <Link
                            href="/subscription"
                            class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200"
                        >
                            {{ document_usage?.at_limit ? 'Upgrade plan' : 'Manage plan' }}
                        </Link>
                    </div>
                </div>
                <div v-if="document_usage" class="mt-5">
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div
                            class="h-full rounded-full transition-all duration-300"
                            :class="document_usage.at_limit ? 'bg-red-500' : 'bg-slate-900 dark:bg-white'"
                            :style="{ width: `${document_usage.percentage}%` }"
                        ></div>
                    </div>
                    <p class="mt-2 text-xs" :class="document_usage.at_limit ? 'font-semibold text-red-600' : 'text-slate-500 dark:text-slate-400'">
                        {{ document_usage.at_limit
                            ? "You've reached your document limit. Upgrade your plan to add more."
                            : document_usage.remaining === null
                                ? 'Unlimited documents available.'
                                : `${document_usage.remaining} documents remaining`
                        }}
                    </p>
                </div>
            </div>

            <!-- Recent activity & recent items -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Recent activity -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <Activity class="h-5 w-5 text-slate-400" />
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Recent activity</h2>
                        </div>
                        <Link href="/activity-logs" class="flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">
                            View all <ArrowUpRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>

                    <div class="relative mb-4">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="activitySearch"
                            @input="onActivitySearchInput"
                            type="text"
                            placeholder="Search activities"
                            class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-9 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-white dark:focus:ring-white"
                        />
                        <button
                            v-if="activitySearch"
                            @click="clearActivitySearch"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div v-if="recent_activity.length" class="space-y-2">
                        <div
                            v-for="activity in recent_activity"
                            :key="activity.id"
                            class="flex items-center gap-3.5 rounded-xl border border-slate-100 p-3.5 dark:border-slate-800"
                        >
                            <div :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', getActionClass(activity.action)]">
                                <component :is="getActionIcon(activity.action)" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-xs font-semibold text-slate-900 dark:text-white">
                                        {{ activity.description || activity.action }}
                                    </p>
                                    <span
                                        v-if="activity.severity"
                                        :class="[
                                            'inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-bold uppercase',
                                            activity.severity === 'critical'
                                                ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'
                                                : activity.severity === 'warning'
                                                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                                    : 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300'
                                        ]"
                                    >
                                        {{ activity.severity }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                                    by <span class="font-medium text-slate-700 dark:text-slate-300">{{ activity.causer_name }}</span> · {{ activity.created_at }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="!hasData" class="py-10 text-center text-xs text-slate-400">No recent activity logged.</div>
                    <div v-else class="flex flex-col items-center justify-center gap-2 py-10 text-xs text-slate-400">
                        <AlertCircle class="h-8 w-8 text-slate-300 dark:text-slate-600" />
                        <p>No recent activity to display.</p>
                    </div>
                </div>

                <!-- Recent items -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <FileText class="h-5 w-5 text-slate-400" />
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Recent items</h2>
                        </div>
                        <Link href="/documents" class="flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">
                            Manage all <ArrowUpRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>

                    <div v-if="recent_documents.length" class="space-y-2">
                        <div
                            v-for="doc in recent_documents"
                            :key="doc.id"
                            class="flex items-center gap-3.5 rounded-xl border border-slate-100 p-3.5 dark:border-slate-800"
                        >
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                <FileText class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-semibold text-slate-900 dark:text-white">{{ doc.doc_name }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ doc.category_title }}</span> · by {{ doc.user_name }}
                                </p>
                            </div>
                            <Link
                                :href="`/documents/${doc.id}`"
                                class="shrink-0 rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-white"
                            >
                                <ArrowUpRight class="h-4 w-4" />
                            </Link>
                        </div>
                    </div>
                    <div v-else-if="!hasData" class="py-10 text-center text-xs text-slate-400">No category items created yet.</div>
                    <div v-else class="flex flex-col items-center justify-center gap-2 py-10 text-xs text-slate-400">
                        <AlertCircle class="h-8 w-8 text-slate-300 dark:text-slate-600" />
                        <p>No recent items to display.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
