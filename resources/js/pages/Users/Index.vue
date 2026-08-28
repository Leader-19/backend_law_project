<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Users as UsersIcon, Plus, Pencil, Trash2, Eye, ShieldCheck, Search, X } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { can } from '@/lib/can';
import ConfirmModal from '@/components/ConfirmModal.vue';
import DataTable from '@/components/ui/data-table/DataTable.vue';
import { ref, computed } from 'vue';

interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    roles: Role[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: '/users' },
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
    };
}>();

const searchQuery = ref(props.filters?.search ?? '');

const isDeleteOpen = ref(false);
const deletingId = ref<number | null>(null);
const deletingName = ref('');

function deleteUser(id: number, name: string) {
    deletingId.value = id;
    deletingName.value = name;
    isDeleteOpen.value = true;
}

function confirmDeleteUser() {
    if (deletingId.value === null) return;
    router.delete(route('users.destroy', deletingId.value), {
        onSuccess: () => {
            isDeleteOpen.value = false;
            deletingId.value = null;
        }
    });
}

function changePage(page: number) {
    if (page < 1 || page > props.pagination.last_page) return;
    router.get(route('users.index'), {
        page,
        per_page: props.pagination.per_page,
        search: searchQuery.value,
    }, { preserveState: true });
}

function changePerPage(perPage: number) {
    router.get(route('users.index'), {
        per_page: perPage,
        page: 1,
        search: searchQuery.value,
    }, { preserveState: true });
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
function onSearchInput() {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('users.index'), {
            search: searchQuery.value,
            page: 1,
        }, { preserveState: true, replace: true });
    }, 400);
}

function clearSearch() {
    searchQuery.value = '';
    router.get(route('users.index'), {}, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Users Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
            <!-- Header section -->
            <section class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <UsersIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">User Accounts</h1>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Manage user registrations, system roles, and category access permissions.</p>
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
                    <Link
                        v-if="can('users.create')"
                        href="/users/create"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors flex-shrink-0"
                    >
                        <Plus class="h-4 w-4" />
                        New User
                    </Link>
                </div>
            </section>

            <!-- Users Table -->
            <DataTable
                :data="users"
                :pagination="pagination"
                :columns="['stt', 'user', 'roles', 'actions']"
                @page-change="changePage"
                @per-page-change="changePerPage"
            >
                <template #header-stt>#</template>
                <template #header-user>User Details</template>
                <template #header-roles>Assigned Roles</template>
                <template #header-actions>Actions</template>

                <template #stt="{ index }">
                    {{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}
                </template>

                <template #user="{ item }">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white font-bold text-sm shadow-sm">
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
                            :class="[
                                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                (role.name || role) === 'Admin' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' : 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300'
                            ]"
                        >
                            <ShieldCheck v-if="(role.name || role) === 'Admin'" class="h-3 w-3" />
                            {{ role.name || role }}
                        </span>
                        <span v-if="!item.roles?.length" class="text-xs text-slate-400 italic">No roles</span>
                    </div>
                </template>

                <template #actions="{ item }">
                    <div class="flex items-center justify-end gap-2">
                        <Link :href="route('users.show', item.id)" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 transition-colors" title="View Profile">
                            <Eye class="h-4 w-4" />
                        </Link>
                        <Link v-if="can('users.edit')" :href="route('users.edit', item.id)" class="rounded-lg p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/50 transition-colors" title="Edit User">
                            <Pencil class="h-4 w-4" />
                        </Link>
                        <button v-if="can('users.delete')" type="button" @click="deleteUser(item.id, item.name)" class="rounded-lg p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/50 transition-colors" title="Delete User">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </template>

                <template #empty>
                    {{ searchQuery ? 'No matching user accounts found.' : 'No users created yet.' }}
                </template>
            </DataTable>

            <!-- Delete User Confirmation Modal -->
            <ConfirmModal
                :open="isDeleteOpen"
                title="Delete User"
                :description="`Are you sure you want to delete '${deletingName}'? This action cannot be undone.`"
                confirm-label="Delete User"
                @confirm="confirmDeleteUser"
                @update:open="isDeleteOpen = $event"
            />
        </main>
    </AppLayout>
</template>
