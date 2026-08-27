<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

interface Props {
    data: any[]
    columns: string[]
    pagination: Pagination
}

const props = defineProps<Props>()

const emit = defineEmits<{
    (e: 'page-change', page: number): void
    (e: 'per-page-change', perPage: number): void
}>()

function changePage(page: number) {
    if (page >= 1 && page <= props.pagination.last_page) {
        emit('page-change', page)
    }
}

function handlePerPageChange(event: Event) {
    const target = event.target as HTMLSelectElement
    const perPage = parseInt(target.value)
    emit('per-page-change', perPage)
}

const visiblePages = computed(() => {
    const current = props.pagination.current_page
    const last = props.pagination.last_page
    const pages: number[] = []

    const start = Math.max(1, current - 2)
    const end = Math.min(last, current + 2)

    for (let i = start; i <= end; i++) {
        pages.push(i)
    }

    return pages
})
</script>

<template>
    <div class="flex flex-col">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-500 dark:text-slate-400">
                    <thead class="border-b text-xs uppercase text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th v-for="column in columns" :key="column" class="px-6 py-3.5 font-semibold">
                                <slot :name="`header-${column}`" :column="column">
                                    {{ column }}
                                </slot>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="(item, index) in data" :key="item.id"
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td v-for="column in columns" :key="column" class="px-6 py-4">
                                <slot :name="column" :item="item" :index="index">
                                    {{ item[column] }}
                                </slot>
                            </td>
                        </tr>
                        <tr v-if="data.length === 0">
                            <td :colspan="columns.length" class="px-6 py-12 text-center text-sm text-slate-400">
                                <slot name="empty">No data found</slot>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total > 0" class="flex items-center justify-between mt-4">
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-500 dark:text-slate-400">
                    {{ pagination.current_page }} / {{ pagination.last_page }} ({{ pagination.total }} total)
                </span>
                <select :value="pagination.per_page" @change="handlePerPageChange"
                    class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1 text-xs text-slate-600 dark:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option :value="10">10 / page</option>
                    <option :value="20">20 / page</option>
                    <option :value="30">30 / page</option>
                    <option :value="50">50 / page</option>
                    <option :value="100">100 / page</option>
                </select>
            </div>

            <div class="flex items-center gap-1">
                <!-- First page -->
                <button v-if="pagination.last_page > 1" @click="changePage(1)" :disabled="pagination.current_page === 1"
                    :class="cn(
                        'px-2 py-1.5 rounded-lg text-xs font-medium transition-colors',
                        pagination.current_page === 1
                            ? 'text-slate-300 dark:text-slate-600 cursor-not-allowed'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                    )">
                    First
                </button>

                <!-- Previous -->
                <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                    :class="cn(
                        'px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                        pagination.current_page === 1
                            ? 'text-slate-300 dark:text-slate-600 cursor-not-allowed'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                    )">
                    Previous
                </button>

                <!-- Page numbers -->
                <button v-for="page in visiblePages" :key="page" @click="changePage(page)" :class="cn(
                    'px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                    page === pagination.current_page
                        ? 'bg-blue-600 text-white shadow-sm'
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                )">
                    {{ page }}
                </button>

                <!-- Next -->
                <button @click="changePage(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page" :class="cn(
                        'px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                        pagination.current_page === pagination.last_page
                            ? 'text-slate-300 dark:text-slate-600 cursor-not-allowed'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                    )">
                    Next
                </button>

                <!-- Last page -->
                <button v-if="pagination.last_page > 1" @click="changePage(pagination.last_page)"
                    :disabled="pagination.current_page === pagination.last_page" :class="cn(
                        'px-2 py-1.5 rounded-lg text-xs font-medium transition-colors',
                        pagination.current_page === pagination.last_page
                            ? 'text-slate-300 dark:text-slate-600 cursor-not-allowed'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                    )">
                    Last
                </button>
            </div>
        </div>
    </div>
</template>
