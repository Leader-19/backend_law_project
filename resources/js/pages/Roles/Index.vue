<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Notebook, Plus, Pencil, Trash2, Eye, ShieldCheck, Search, X } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { can } from '@/lib/can';
import ConfirmModal from '@/components/ConfirmModal.vue';
import DataTable from '@/components/ui/data-table/DataTable.vue';
import { ref, computed } from 'vue';

interface Permission {
    id?: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Roles', href: '/roles' },
];

const props = defineProps<{
    roles: Role[];
    pagination: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}>();

const searchQuery = ref('');

const filteredRoles = computed(() => {
    if (!searchQuery.value.trim()) return props.roles;
    const query = searchQuery.value.toLowerCase();
    return props.roles.filter(r =>
        r.name.toLowerCase().includes(query) ||
        (Array.isArray(r.permissions) ? r.permissions : []).some(p => p.name.toLowerCase().includes(query))
    );
});

const isDeleteOpen = ref(false);
const deletingId = ref<number | null>(null);
const deletingName = ref('');

function deleteRole(id: number, name: string) {
    deletingId.value = id;
    deletingName.value = name;
    isDeleteOpen.value = true;
}

function confirmDeleteRole() {
    if (deletingId.value === null) return;
    router.delete(route('roles.destroy', deletingId.value), {
        onSuccess: () => {
            isDeleteOpen.value = false;
            deletingId.value = null;
        }
    });
}

function changePage(page: number) {
    if (page < 1 || page > props.pagination.last_page) return;
    router.get(route('roles.index'), {
        page,
        per_page: props.pagination.per_page,
        search: searchQuery.value,
    }, { preserveState: true });
}

function changePerPage(perPage: number) {
    router.get(route('roles.index'), {
        per_page: perPage,
        page: 1,
        search: searchQuery.value,
    }, { preserveState: true });
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
function onSearchInput() {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('roles.index'), {
            search: searchQuery.value,
            page: 1,
        }, { preserveState: true, replace: true });
    }, 400);
}

function clearSearch() {
    searchQuery.value = '';
    router.get(route('roles.index'), {}, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Roles Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
            <!-- Header section -->
            <section class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <Notebook class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Role Management</h1>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Manage system roles, permissions, and access control assignments.</p>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <div class="relative flex-1 sm:w-64">
                        <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            @input="onSearchInput"
                            type="text"
                            placeholder="Search roles or permissions..."
                            class="w-full rounded-xl border border-slate-300 pl-9 pr-3 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
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
                        v-if="can('roles.create')"
                        href="/roles/create"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors flex-shrink-0"
                    >
                        <Plus class="h-4 w-4" />
                        New Role
                    </Link>
                </div>
            </section>

            <!-- Roles Table -->
            <DataTable
                :data="filteredRoles"
                :pagination="pagination"
                :columns="['stt', 'role', 'permissions', 'actions']"
                @page-change="changePage"
                @per-page-change="changePerPage"
            >
                <template #header-stt>#</template>
                <template #header-role>Role Name</template>
                <template #header-permissions>Permissions</template>
                <template #header-actions>Actions</template>

                <template #stt="{ index }">
                    {{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}
                </template>

                <template #role="{ item }">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 text-white font-bold text-sm shadow-sm">
                            {{ item.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <div class="font-semibold text-slate-900 dark:text-white">{{ item.name }}</div>
                            <div class="text-xs text-slate-400">{{ (Array.isArray(item.permissions) ? item.permissions : []).length }} permission(s)</div>
                        </div>
                    </div>
                </template>

                <template #permissions="{ item }">
                    <div class="flex flex-wrap gap-1.5 max-w-lg">
                        <span
                            v-for="(permission, pIdx) in (Array.isArray(item.permissions) ? item.permissions : []).slice(0, 4)"
                            :key="pIdx"
                            class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                        >
                            {{ permission.name }}
                        </span>
                        <span
                            v-if="(Array.isArray(item.permissions) ? item.permissions : []).length > 4"
                            class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                        >
                            +{{ (Array.isArray(item.permissions) ? item.permissions : []).length - 4 }} more
                        </span>
                        <span v-if="!(Array.isArray(item.permissions) ? item.permissions : []).length" class="text-xs text-slate-400 italic">No permissions</span>
                    </div>
                </template>

                <template #actions="{ item }">
                    <div class="flex items-center justify-end gap-2">
                        <Link :href="route('roles.show', item.id)" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 transition-colors" title="View Role">
                            <Eye class="h-4 w-4" />
                        </Link>
                        <Link v-if="can('roles.edit')" :href="route('roles.edit', item.id)" class="rounded-lg p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/50 transition-colors" title="Edit Role">
                            <Pencil class="h-4 w-4" />
                        </Link>
                        <button v-if="can('roles.delete')" type="button" @click="deleteRole(item.id, item.name)" class="rounded-lg p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/50 transition-colors" title="Delete Role">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </template>

                <template #empty>
                    {{ searchQuery ? 'No matching roles found.' : 'No roles created yet.' }}
                </template>
            </DataTable>

            <!-- Delete Role Confirmation Modal -->
            <ConfirmModal
                :open="isDeleteOpen"
                title="Delete Role"
                :description="`Are you sure you want to delete the role '${deletingName}'? Users assigned to this role will lose its permissions. This action cannot be undone.`"
                confirm-label="Delete Role"
                @confirm="confirmDeleteRole"
                @update:open="isDeleteOpen = $event"
            />
        </main>
    </AppLayout>
</template>
