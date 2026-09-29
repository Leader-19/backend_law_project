<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { can } from '@/lib/can'
import { type BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { CheckCircle2, RefreshCw, ShieldCheck, Search, X } from '@lucide/vue'
import { ref } from 'vue'
import { route } from 'ziggy-js'

interface Permission { id: number; name: string; guard_name: string; roles_count: number; created_at: string }
interface Pagination { data: Permission[]; current_page: number; last_page: number; per_page: number; total: number }
const props = defineProps<{ permissions: Pagination; filters: { search: string } }>()
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Permissions', href: '/permissions' }]
const search = ref(props.filters.search ?? '')
const scanning = ref(false)
const notice = ref('')

let searchTimeout: ReturnType<typeof setTimeout> | null = null
function onSearchInput() {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get(route('permissions.index'), { search: search.value, page: 1 }, { preserveState: true, replace: true })
    }, 400)
}

function clearSearch() {
    search.value = ''
    router.get(route('permissions.index'), {}, { preserveState: true, replace: true })
}

function go(page: number) {
    if (page < 1 || page > props.permissions.last_page) return
    router.get(route('permissions.index'), { search: search.value, page }, { preserveState: true, preserveScroll: true })
}

function changePerPage(perPage: number) {
    router.get(route('permissions.index'), { search: search.value, per_page: perPage, page: 1 }, { preserveState: true })
}

function scanRoutes() {
    scanning.value = true
    router.post(route('permissions.scan'), {}, {
        preserveScroll: true,
        onSuccess: () => { notice.value = 'Route scan completed. Admin has been synchronized with every permission.' },
        onFinish: () => { scanning.value = false },
    })
}
</script>

<template>
    <Head title="Permissions" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto max-w-8xl space-y-6 p-4 sm:p-6">
            <!-- Header Section -->
            <section class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 rounded-[5px] border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <ShieldCheck class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Permissions</h1>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Permissions are created from named application routes. Scanning adds missing permissions.</p>
                </div>
                <button v-if="can('roles.edit')" :disabled="scanning" class="inline-flex items-center gap-2 rounded-[5px] bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-60 transition-colors" @click="scanRoutes">
                    <RefreshCw :class="['h-4 w-4', { 'animate-spin': scanning }]" />
                    {{ scanning ? 'Scanning...' : 'Scan Routes' }}
                </button>
            </section>

            <!-- Notice Banner -->
            <div v-if="notice" class="flex items-center gap-2 rounded-[5px] bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-700 dark:bg-emerald-950 dark:border-emerald-800 dark:text-emerald-300">
                <CheckCircle2 class="h-5 w-5 flex-shrink-0" />
                {{ notice }}
            </div>

            <!-- Permissions Table -->
            <section class="overflow-hidden rounded-[5px] border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                <!-- Search Bar -->
                <div class="border-b border-slate-100 dark:border-slate-800 px-6 py-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <p class="text-sm text-slate-500">{{ permissions.total }} permissions total. Scanning adds missing permissions and gives all permissions to Admin.</p>
                        <div class="relative w-full sm:w-64">
                            <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                            <input
                                v-model="search"
                                @input="onSearchInput"
                                type="text"
                                placeholder="Search permissions..."
                                class="w-full rounded-[5px] border border-slate-300 pl-9 pr-9 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                            <button
                                v-if="search"
                                @click="clearSearch"
                                class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                        <thead class="border-b text-xs uppercase text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-800">
                            <tr>
                                <th class="px-6 py-3.5">#</th>
                                <th class="px-6 py-3.5">Permission</th>
                                <th class="px-6 py-3.5">Guard</th>
                                <th class="px-6 py-3.5 text-right">Roles Using It</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="(permission, index) in permissions.data" :key="permission.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4 text-slate-500 font-medium">
                                    {{ (permissions.current_page - 1) * permissions.per_page + index + 1 }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        {{ permission.name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500 text-xs">{{ permission.guard_name }}</td>
                                <td class="px-6 py-4 text-right">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ permission.roles_count }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="permissions.data.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-400">
                                    {{ search ? 'No matching permissions found.' : 'No permissions found.' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="permissions.last_page > 1" class="flex items-center justify-between border-t border-slate-100 dark:border-slate-800 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-slate-500">
                            Page {{ permissions.current_page }} of {{ permissions.last_page }} ({{ permissions.total }} total)
                        </span>
                        <select
                            :value="permissions.per_page"
                            @change="changePerPage(Number(($event.target as HTMLSelectElement).value))"
                            class="rounded-lg border border-slate-200 px-2 py-1 text-xs dark:border-slate-700 dark:bg-slate-900 focus:outline-none"
                        >
                            <option :value="10">10 / page</option>
                            <option :value="20">20 / page</option>
                            <option :value="50">50 / page</option>
                            <option :value="100">100 / page</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button
                            :disabled="permissions.current_page <= 1"
                            @click="go(permissions.current_page - 1)"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:hover:bg-gray-800 transition-colors"
                        >
                            Previous
                        </button>
                        <button
                            :disabled="permissions.current_page >= permissions.last_page"
                            @click="go(permissions.current_page + 1)"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:hover:bg-gray-800 transition-colors"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </section>
        </main>
    </AppLayout>
</template>
