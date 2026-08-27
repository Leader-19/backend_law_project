<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Users as UsersIcon, Trash2, ShieldCheck, Search, X, FolderTree, CreditCard } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { can } from '@/lib/can';
import ConfirmModal from '@/components/ConfirmModal.vue';
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
        <main class="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
            <!-- Header section -->
            <section class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
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
                            class="w-full rounded-xl border border-slate-300 pl-9 pr-9 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
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
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                        <thead class="border-b text-xs uppercase text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-800">
                            <tr>
                                <th class="px-6 py-3.5">#</th>
                                <th class="px-6 py-3.5">User Details</th>
                                <th class="px-6 py-3.5">Assigned Roles</th>
                                <th class="px-6 py-3.5">Registered</th>
                                <th class="px-6 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr v-for="(user, index) in users" :key="user.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4 text-slate-500 font-medium">
                                    {{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            v-if="user.avatar_url"
                                            :src="user.avatar_url"
                                            class="h-10 w-10 rounded-full object-cover shadow-sm"
                                        />
                                        <div v-else class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-sm shadow-sm">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-900 dark:text-white">{{ user.name }}</div>
                                            <div class="text-xs text-slate-400">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="role in user.roles"
                                            :key="role.id"
                                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                                        >
                                            <ShieldCheck v-if="role.name === 'Admin'" class="h-3 w-3" />
                                            {{ role.name }}
                                        </span>
                                        <span v-if="!user.roles?.length" class="text-xs text-slate-400 italic">No roles</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400">
                                    {{ user.created_at }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/frontend-users/${user.id}/categories`" class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-700 transition-colors" title="Assign Categories">
                                            <FolderTree class="h-3.5 w-3.5" />
                                            Categories
                                        </Link>
                                        <Link :href="`/frontend-users/${user.id}`" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition-colors" title="Subscribe to Plan">
                                            <CreditCard class="h-3.5 w-3.5" />
                                            Plan
                                        </Link>
                                        <button v-if="can('users.delete')" type="button" @click="deleteUser(user.id)" class="rounded-lg p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/50 transition-colors" title="Delete User">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!users.length">
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-slate-400">
                                    {{ searchQuery ? 'No matching users found.' : 'No frontend registered users found.' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.last_page > 1" class="flex items-center justify-between border-t border-slate-100 dark:border-slate-800 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-slate-500">
                            Page {{ pagination.current_page }} of {{ pagination.last_page }} ({{ pagination.total }} total)
                        </span>
                        <select
                            :value="pagination.per_page"
                            @change="changePerPage(Number(($event.target as HTMLSelectElement).value))"
                            class="rounded-lg border border-slate-200 px-2 py-1 text-xs dark:border-slate-700 dark:bg-slate-900 focus:outline-none"
                        >
                            <option :value="10">10 / page</option>
                            <option :value="25">25 / page</option>
                            <option :value="50">50 / page</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button
                            :disabled="pagination.current_page <= 1"
                            @click="goToPage(pagination.current_page - 1)"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:hover:bg-gray-800 transition-colors"
                        >
                            Previous
                        </button>
                        <button
                            :disabled="pagination.current_page >= pagination.last_page"
                            @click="goToPage(pagination.current_page + 1)"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:hover:bg-gray-800 transition-colors"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </section>

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
