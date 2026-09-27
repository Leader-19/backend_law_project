<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
// import { Users as UsersIcon, Trash2, Shield, Search, X, FolderTree, CreditCard } from '@lucide/vue';
import { Users as UsersIcon, Trash2, Shield, Search, X, FolderTree, CreditCard } from '@lucide/vue';
import { route } from 'ziggy-js';
import { can } from '@/lib/can';
import ConfirmModal from '@/components/ConfirmModal.vue';
import DataTable from '@/components/ui/data-table/DataTable.vue';
import { ref } from 'vue';

interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    avatar_url?: string;
    roles: Role[];
    created_at: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: '/users' },
    { title: 'Frontend Registrations', href: '/frontend-users' },
];

const props = defineProps<{
    users: User[];
    pagination: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search: string;
        per_page: number;
    };
}>();

const searchQuery = ref(props.filters.search ?? '');

const isDeleteOpen = ref(false);
const deletingId = ref<number | null>(null);

function initials(name: string) {
    const parts = name.trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) return '?';
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function roleName(role: Role | string) {
    return typeof role === 'string' ? role : role.name;
}

function deleteUser(id: number) {
    deletingId.value = id;
    isDeleteOpen.value = true;
}

function confirmDeleteUser() {
    if (deletingId.value === null) return;
    router.delete(route('frontend-users.destroy', deletingId.value), {
        onSuccess: () => {
            isDeleteOpen.value = false;
            deletingId.value = null;
        }
    });
}

function goToPage(page: number) {
    if (page < 1 || page > props.pagination.last_page) return;
    router.get(route('frontend-users.index'), {
        page,
        per_page: props.pagination.per_page,
        search: searchQuery.value,
    }, { preserveState: true });
}

function changePerPage(perPage: number) {
    router.get(route('frontend-users.index'), {
        per_page: perPage,
        page: 1,
        search: searchQuery.value,
    }, { preserveState: true });
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
function onSearchInput() {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('frontend-users.index'), {
            search: searchQuery.value,
            page: 1,
        }, { preserveState: true, replace: true });
    }, 400);
}

function clearSearch() {
    searchQuery.value = '';
    router.get(route('frontend-users.index'), {}, { preserveState: true, replace: true });
}
</script>

<template>

    <Head title="Frontend Registered Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto w-full max-w-none space-y-6 p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <section
                class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-900 text-white dark:bg-white dark:text-slate-900">
                        <UsersIcon class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Frontend registrations</h1>
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                            {{ pagination.total }} {{ pagination.total === 1 ? 'account' : 'accounts' }} registered
                            through the public site
                        </p>
                    </div>
                </div>
                <div class="relative w-full sm:w-64">
                    <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                    <input v-model="searchQuery" @input="onSearchInput" type="text"
                        placeholder="Search by name or email"
                        class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-9 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-white dark:focus:ring-white" />
                    <button v-if="searchQuery" @click="clearSearch"
                        class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </section>

            <!-- Users table -->
            <DataTable :data="users" :pagination="pagination"
                :columns="['stt', 'user', 'roles', 'registered', 'actions']" @page-change="goToPage"
                @per-page-change="changePerPage">
                <template #header-stt>#</template>
                <template #header-user>User</template>
                <template #header-roles>Roles</template>
                <template #header-registered>Registered</template>
                <template #header-actions>Actions</template>

                <template #stt="{ index }">
                    {{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}
                </template>

                <template #user="{ item }">
                    <div class="flex items-center gap-3">
                        <img v-if="item.avatar_url" :src="item.avatar_url"
                            class="h-9 w-9 shrink-0 rounded-full object-cover" />
                        <div v-else
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white dark:bg-white dark:text-slate-900">
                            {{ initials(item.name) }}
                        </div>
                        <div class="min-w-0">
                            <div class="truncate font-medium text-slate-900 dark:text-white">{{ item.name }}</div>
                            <div class="truncate text-xs text-slate-500 dark:text-slate-400">{{ item.email }}</div>
                        </div>
                    </div>
                </template>

                <template #roles="{ item }">
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="role in item.roles" :key="(role as any).id ?? roleName(role)"
                            class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-medium capitalize text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            <Shield class="h-3 w-3 text-slate-400" />
                            {{ roleName(role) }}
                        </span>
                        <span v-if="!item.roles?.length" class="text-xs italic text-slate-400">No roles</span>
                    </div>
                </template>

                <template #registered="{ item }">
                    <span class="text-sm text-slate-500 dark:text-slate-400">{{ item.created_at }}</span>
                </template>

                <template #actions="{ item }">
                    <div class="flex items-center justify-end gap-1.5">
                        <Link :href="`/frontend-users/${item.id}/categories`"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            title="Assign categories">
                            <FolderTree class="h-3.5 w-3.5" />
                            Categories
                        </Link>
                        <Link :href="`/frontend-users/${item.id}`"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            title="Subscribe to plan">
                            <CreditCard class="h-3.5 w-3.5" />
                            Plan
                        </Link>
                        <button v-if="can('users.delete')" type="button" @click="deleteUser(item.id)"
                            class="rounded-lg p-2 text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-950/50"
                            title="Delete user">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </template>

                <template #empty>
                    {{ searchQuery ? 'No matching users found.' : 'No frontend registered users found.' }}
                </template>
            </DataTable>

            <!-- Delete confirmation -->
            <ConfirmModal :open="isDeleteOpen" title="Delete user"
                description="Are you sure you want to delete this frontend user account? This can't be undone."
                confirm-label="Delete user" @confirm="confirmDeleteUser" @update:open="isDeleteOpen = $event" />
        </main>
    </AppLayout>
</template>
