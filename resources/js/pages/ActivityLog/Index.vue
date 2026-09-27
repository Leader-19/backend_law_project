<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { Trash2, Search, Filter, X, AlertTriangle, AlertCircle, Info, CheckCircle, Database, RefreshCw } from '@lucide/vue';
import DataTable from '@/components/ui/data-table/DataTable.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Activity Logs', href: '/activity-logs' },
];

const actionLabel: Record<string, string> = {
    created: 'Created',
    updated: 'Updated',
    deleted: 'Deleted',
    issue: 'System Issue',
    health_check: 'Health Check',
};

const actionClass: Record<string, string> = {
    created: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
    updated: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    deleted: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
    issue: 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300',
    health_check: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
};

const severityConfig: Record<string, { class: string; icon: any }> = {
    critical: {
        class: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300 border border-red-200 dark:border-red-800',
        icon: AlertCircle,
    },
    warning: {
        class: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
        icon: AlertTriangle,
    },
    info: {
        class: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800',
        icon: Info,
    },
};

const props = defineProps<{
    logs: Array<{
        id: number;
        action: string;
        severity: string | null;
        description: string | null;
        old_data: Record<string, unknown> | null;
        new_data: Record<string, unknown> | null;
        ip_address: string | null;
        user_agent: string | null;
        created_at: string;
        causer: { id: number; name: string; email: string } | null;
    }>;
    pagination: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        action: string | null;
        search: string | null;
        user_id: string | null;
    };
}>();

const selectedAction = ref<string>(props.filters?.action ?? '');
const searchQuery = ref<string>(props.filters?.search ?? '');
const selectedIds = ref<number[]>([]);

const isAllSelected = computed(() =>
    props.logs.length > 0 && props.logs.every(log => selectedIds.value.includes(log.id))
);

const isDeleteOpen = ref(false);
const isClearAllOpen = ref(false);
const deleteTarget = ref<string>('');

function formatValue(value: unknown): string {
    if (value === null) return 'null';
    if (typeof value === 'boolean') return value ? 'true' : 'false';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
}

function applyFilters() {
    const params: Record<string, string> = {};
    if (selectedAction.value) params.action = selectedAction.value;
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
    router.get('/activity-logs', params, {
        preserveState: true,
        replace: true,
    });
}

function clearFilters() {
    selectedAction.value = '';
    searchQuery.value = '';
    router.get('/activity-logs', {}, { preserveState: true, replace: true });
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
function onSearchInput() {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
}

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.logs.map(log => log.id);
    }
}

function deleteSingle(id: number) {
    deleteTarget.value = 'single';
    selectedIds.value = [id];
    isDeleteOpen.value = true;
}

function deleteSelected() {
    if (selectedIds.value.length === 0) return;
    deleteTarget.value = 'bulk';
    isDeleteOpen.value = true;
}

function confirmDelete() {
    if (deleteTarget.value === 'clear') {
        router.delete('/activity-logs/clear', {
            onFinish: () => {
                isClearAllOpen.value = false;
                selectedIds.value = [];
            },
        });
    } else if (deleteTarget.value === 'bulk') {
        router.delete('/activity-logs/bulk', {
            data: { ids: selectedIds.value },
            onFinish: () => {
                isDeleteOpen.value = false;
                selectedIds.value = [];
            },
        });
    } else if (deleteTarget.value === 'single' && selectedIds.value.length === 1) {
        router.delete(`/activity-logs/${selectedIds.value[0]}`, {
            onFinish: () => {
                isDeleteOpen.value = false;
                selectedIds.value = [];
            },
        });
    }
}

function confirmClearAll() {
    deleteTarget.value = 'clear';
    isClearAllOpen.value = true;
}

function changePage(page: number) {
    if (page < 1 || page > props.pagination.last_page) return;
    const params: Record<string, string | number> = { page, per_page: props.pagination.per_page };
    if (selectedAction.value) params.action = selectedAction.value;
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
    router.get('/activity-logs', params, { preserveState: true });
}

function changePerPage(perPage: number) {
    const params: Record<string, string | number> = { per_page: perPage, page: 1 };
    if (selectedAction.value) params.action = selectedAction.value;
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
    router.get('/activity-logs', params, { preserveState: true });
}

function runHealthCheck() {
    router.get('/activity-logs/health-check', {}, {
        preserveState: true,
        replace: true,
    });
}

function isIssueLog(log: any) {
    return log.action === 'issue' || log.action === 'health_check';
}
</script>

<template>
    <Head title="Activity Logs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">Activity Logs</h1>
                    <p class="text-sm text-slate-500">{{ pagination.total }} total entries</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="runHealthCheck"
                        class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-white px-3 py-2 text-xs font-semibold text-emerald-600 hover:bg-emerald-50 transition-colors dark:border-emerald-800 dark:bg-slate-900 dark:text-emerald-400"
                    >
                        <RefreshCw class="h-3.5 w-3.5" />
                        DB Health Check
                    </button>
                    <button
                        v-if="selectedIds.length > 0"
                        @click="deleteSelected"
                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700 transition-colors"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        Delete ({{ selectedIds.length }})
                    </button>
                    <button
                        @click="confirmClearAll"
                        class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors dark:border-red-800 dark:bg-slate-900 dark:text-red-400"
                    >
                        <AlertTriangle class="h-3.5 w-3.5" />
                        Clear All
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <div class="relative flex-1 w-full sm:max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        @input="onSearchInput"
                        type="text"
                        placeholder="Search description, IP, user..."
                        class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-4 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''; applyFilters()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <select
                    v-model="selectedAction"
                    @change="applyFilters"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All actions</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                    <option value="issue">System Issues</option>
                    <option value="health_check">Health Checks</option>
                </select>

                <button
                    v-if="selectedAction || searchQuery"
                    @click="clearFilters"
                    class="inline-flex items-center gap-1 rounded-xl border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-400"
                >
                    <X class="h-3.5 w-3.5" /> Clear filters
                </button>
            </div>

            <!-- Table -->
            <DataTable
                :data="logs"
                :pagination="pagination"
                :columns="['select', 'id', 'action', 'description', 'user', 'ip', 'details', 'date', 'actions']"
                @page-change="changePage"
                @per-page-change="changePerPage"
            >
                <template #header-select>
                    <input
                        type="checkbox"
                        :checked="isAllSelected"
                        @change="toggleSelectAll"
                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    />
                </template>
                <template #select="{ item }">
                    <input
                        v-model="selectedIds"
                        type="checkbox"
                        :value="item.id"
                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    />
                </template>
                <template #header-id>#</template>
                <template #header-action>Action</template>
                <template #header-description>Description</template>
                <template #header-user>User</template>
                <template #header-ip>IP</template>
                <template #header-details>Details</template>
                <template #header-date>Date</template>
                <template #header-actions>Actions</template>

                <template #id="{ item }">
                    <span class="text-gray-500">{{ item.id }}</span>
                </template>

                <template #action="{ item }">
                    <div class="flex items-center gap-2">
                        <span
                            class="rounded px-2 py-1 text-xs font-medium"
                            :class="actionClass[item.action] ?? 'bg-gray-100 text-gray-700'"
                        >
                            {{ actionLabel[item.action] ?? item.action }}
                        </span>
                        <span
                            v-if="item.severity"
                            class="inline-flex items-center gap-1 rounded px-2 py-1 text-[10px] font-bold uppercase"
                            :class="severityConfig[item.severity]?.class ?? 'bg-gray-100 text-gray-600'"
                        >
                            <component :is="severityConfig[item.severity]?.icon ?? AlertCircle" class="h-3 w-3" />
                            {{ item.severity }}
                        </span>
                    </div>
                </template>

                <template #description="{ item }">
                    <div class="max-w-xs truncate">
                        <div class="flex items-center gap-2">
                            <Database v-if="isIssueLog(item)" class="h-4 w-4 text-orange-500 flex-shrink-0" />
                            <span>{{ item.description }}</span>
                        </div>
                    </div>
                </template>

                <template #user="{ item }">
                    <span class="font-medium">{{ item.causer?.name ?? 'Automated task' }}</span>
                </template>

                <template #ip="{ item }">
                    <span class="text-gray-500 text-xs">{{ item.ip_address }}</span>
                </template>

                <template #details="{ item }">
                    <div v-if="item.new_data && isIssueLog(item)" class="space-y-1 max-w-xs">
                        <div v-for="(value, key) in item.new_data" :key="'d-' + key" class="text-xs">
                            <span class="font-medium text-slate-600 dark:text-slate-400">{{ key }}:</span>
                            <span class="text-orange-600 dark:text-orange-400 ml-1">{{ formatValue(value) }}</span>
                        </div>
                    </div>
                    <div v-else-if="item.new_data" class="space-y-1 max-w-xs">
                        <div v-for="(value, key) in item.new_data" :key="'n-' + key" class="text-xs">
                            <span class="font-medium">{{ key }}</span>:
                            <span class="text-green-600">{{ formatValue(value) }}</span>
                            <span
                                v-if="item.old_data && key in item.old_data"
                                class="text-gray-400"
                            >
                                (was {{ formatValue(item.old_data[key]) }})
                            </span>
                        </div>
                    </div>
                    <div v-else-if="item.old_data" class="space-y-1 max-w-xs">
                        <div v-for="(value, key) in item.old_data" :key="'o-' + key" class="text-xs">
                            <span class="font-medium">{{ key }}</span>:
                            <span class="text-red-600 line-through">{{ formatValue(value) }}</span>
                        </div>
                    </div>
                    <span v-else class="text-gray-400">—</span>
                </template>

                <template #date="{ item }">
                    <span class="text-gray-500 text-xs whitespace-nowrap">{{ item.created_at }}</span>
                </template>

                <template #actions="{ item }">
                    <button
                        @click="deleteSingle(item.id)"
                        class="p-1.5 rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors dark:hover:bg-red-950"
                        title="Delete log"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </template>

                <template #empty>No activity logs found.</template>
            </DataTable>

            <!-- Delete Confirmation Modal -->
            <ConfirmModal
                :open="isDeleteOpen"
                title="Delete Activity Log(s)"
                :description="deleteTarget === 'clear'
                    ? 'Are you sure you want to clear ALL activity logs? This action cannot be undone.'
                    : `Are you sure you want to delete ${selectedIds.length} selected log(s)? This action cannot be undone.`"
                confirm-label="Delete"
                @confirm="confirmDelete"
                @update:open="isDeleteOpen = $event"
            />

            <!-- Clear All Confirmation Modal -->
            <ConfirmModal
                :open="isClearAllOpen"
                title="Clear All Activity Logs"
                description="Are you sure you want to delete ALL activity logs? This action cannot be undone and will permanently remove all audit history."
                confirm-label="Clear All"
                @confirm="confirmClearAll"
                @update:open="isClearAllOpen = $event"
            />
        </div>
    </AppLayout>
</template>
