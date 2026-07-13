<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Activity Logs', href: '/activity-logs' },
];

const actionLabel: Record<string, string> = {
    created: 'Created',
    updated: 'Updated',
    deleted: 'Deleted',
};

const actionClass: Record<string, string> = {
    created: 'bg-green-100 text-green-700',
    updated: 'bg-blue-100 text-blue-700',
    deleted: 'bg-red-100 text-red-700',
};

const props = defineProps<{
    logs: Array<{
        id: number;
        action: string;
        description: string | null;
        old_data: Record<string, unknown> | null;
        new_data: Record<string, unknown> | null;
        ip_address: string | null;
        created_at: string;
        causer: { id: number; name: string } | null;
    }>;
    pagination: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        action: string | null;
    };
}>();

const selectedAction = ref<string>(props.filters?.action ?? '');

function formatValue(value: unknown): string {
    if (value === null) return 'null';
    if (typeof value === 'boolean') return value ? 'true' : 'false';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
}

function onFilter() {
    router.get('/activity-logs', { action: selectedAction.value || null }, {
        preserveState: true,
        replace: true,
    });
}

watch(selectedAction, onFilter);
</script>

<template>
    <Head title="Activity Logs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Activity Logs</h1>

                <select
                    v-model="selectedAction"
                    class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800"
                >
                    <option value="">All actions</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                </select>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Action</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">IP</th>
                            <th class="px-4 py-3">Changes</th>
                            <th class="px-4 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-for="log in logs" :key="log.id">
                            <td class="px-4 py-3 text-gray-500">{{ log.id }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded px-2 py-1 text-xs font-medium"
                                    :class="actionClass[log.action] ?? 'bg-gray-100 text-gray-700'"
                                >
                                    {{ actionLabel[log.action] ?? log.action }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ log.description }}</td>
                            <td class="px-4 py-3">{{ log.causer?.name ?? 'System' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ log.ip_address }}</td>
                            <td class="px-4 py-3">
                                <div v-if="log.new_data" class="space-y-1">
                                    <div v-for="(value, key) in log.new_data" :key="'n-' + key">
                                        <span class="font-medium">{{ key }}</span>:
                                        <span class="text-green-600">{{ formatValue(value) }}</span>
                                        <span
                                            v-if="log.old_data && key in log.old_data"
                                            class="text-gray-400"
                                        >
                                            (was {{ formatValue(log.old_data[key]) }})
                                        </span>
                                    </div>
                                </div>
                                <div v-else-if="log.old_data" class="space-y-1">
                                    <div v-for="(value, key) in log.old_data" :key="'o-' + key">
                                        <span class="font-medium">{{ key }}</span>:
                                        <span class="text-red-600 line-through">{{ formatValue(value) }}</span>
                                    </div>
                                </div>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ log.created_at }}</td>
                        </tr>
                        <tr v-if="logs.length === 0">
                            <td colspan="7" class="px-4 py-6 text-center text-gray-400">
                                No activity logged yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between text-sm text-gray-500">
                <span>
                    Page {{ pagination.current_page }} of {{ pagination.last_page }}
                    ({{ pagination.total }} total)
                </span>
                <div class="flex gap-2">
                    <Link
                        v-if="pagination.current_page > 1"
                        :href="`/activity-logs?page=${pagination.current_page - 1}${selectedAction ? '&action=' + selectedAction : ''}`"
                        class="rounded border border-gray-300 px-3 py-1 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-800"
                    >
                        Previous
                    </Link>
                    <Link
                        v-if="pagination.current_page < pagination.last_page"
                        :href="`/activity-logs?page=${pagination.current_page + 1}${selectedAction ? '&action=' + selectedAction : ''}`"
                        class="rounded border border-gray-300 px-3 py-1 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-800"
                    >
                        Next
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
