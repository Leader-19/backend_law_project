<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Users as UsersIcon, Trash2, ShieldCheck, Search, X, FolderTree, CreditCard } from 'lucide-vue-next';
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
        <main class="mx-auto max-w-8xl space-y-6 p-4 sm:p-6">
            <!-- Header section -->
            <section class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 rounded-[5px] border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <UsersIcon class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Frontend Registered Users</h1>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Manage users who registered through the public-facing application.</p>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <div class="relative flex-1 sm:w-64">
                        <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            @input="onSearchInput"
                            type="text"
                            placeholder="Search users..."
                            class="w-full rounded-[5px] border border-slate-300 pl-9 pr-9 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <button
                            v-if="searchQuery"
                            @click="clearSearch"
                            class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </section>

            <!-- Users Table -->
            <DataTable
                :data="users"
                :pagination="pagination"
                :columns="['stt', 'user', 'roles', 'registered', 'actions']"
                @page-change="goToPage"
                @per-page-change="changePerPage"
            >
                <template #header-stt>#</template>
                <template #header-user>User Details</template>
                <template #header-roles>Assigned Roles</template>
                <template #header-registered>Registered</template>
                <template #header-actions>Actions</template>

                <template #stt="{ index }">
                    {{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}
                </template>

                <template #user="{ item }">
                    <div class="flex items-center gap-3">
                        <img
                            v-if="item.avatar_url"
                            :src="item.avatar_url"
                            class="h-10 w-10 rounded-full object-cover shadow-sm"
                        />
                        <div v-else class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-sm shadow-sm">
                            {{ item.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <div class="font-semibold text-slate-900 dark:text-white">{{ item.name }}</div>
                            <div class="text-xs text-slate-400">{{ item.email }}</div>
                        </div>
                    </div>
                </template>

                <template #roles="{ item }">
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="role in item.roles"
                            :key="role.id || role"
                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                        >
                            <ShieldCheck v-if="(role.name || role) === 'Admin'" class="h-3 w-3" />
                            {{ role.name || role }}
                        </span>
                        <span v-if="!item.roles?.length" class="text-xs text-slate-400 italic">No roles</span>
                    </div>
                </template>

                <template #registered="{ item }">
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ item.created_at }}</span>
                </template>

                <template #actions="{ item }">
                    <div class="flex items-center justify-end gap-2">
                        <Link :href="`/frontend-users/${item.id}/categories`" class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-700 transition-colors" title="Assign Categories">
                            <FolderTree class="h-3.5 w-3.5" />
                            Categories
                        </Link>
                        <Link :href="`/frontend-users/${item.id}`" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition-colors" title="Subscribe to Plan">
                            <CreditCard class="h-3.5 w-3.5" />
                            Plan
                        </Link>
                        <button v-if="can('users.delete')" type="button" @click="deleteUser(item.id)" class="rounded-lg p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/50 transition-colors" title="Delete User">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </template>

                <template #empty>
                    {{ searchQuery ? 'No matching users found.' : 'No frontend registered users found.' }}
                </template>
            </DataTable>

            <!-- Delete User Confirmation Modal -->
            <ConfirmModal
                :open="isDeleteOpen"
                title="Delete User"
                description="Are you sure you want to delete this frontend user account? This action cannot be undone."
                confirm-label="Delete User"
                @confirm="confirmDeleteUser"
                @update:open="isDeleteOpen = $event"
            />
        </main>
    </AppLayout>
</template>
