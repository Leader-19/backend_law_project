<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { Check, FolderTree, Save, Search, X } from 'lucide-vue-next'
import { computed, ref } from 'vue'

interface Category { id: number; title: string; parent_id: number | null }
interface Plan { id: number; name: string; categories: Category[] }
interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const props = defineProps<{
    plan: Plan
    categories: Category[]
    all_category_ids?: number[]
    pagination: Pagination
    filters: { search: string }
}>()

const selectedIds = ref<number[]>(props.plan.categories.map(category => category.id))

function selectAll() {
    if (props.all_category_ids?.length) {
        selectedIds.value = [...props.all_category_ids]
    } else {
        selectedIds.value = props.categories.map(category => category.id)
    }
}

function deselectAll() {
    selectedIds.value = []
}
const saving = ref(false)
const searchQuery = ref(props.filters.search ?? '')
const categoriesById = computed(() => {
    // Build from both paginated categories AND plan's assigned categories
    const allCats = [...props.categories, ...props.plan.categories]
    return new Map(allCats.map(category => [category.id, category]))
})

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Subscription Plans', href: '/subscription-plans' },
    { title: 'Plan Categories', href: '#' },
]

function categoryLabel(category: Category): string {
    const parent = category.parent_id ? categoriesById.value.get(category.parent_id) : undefined
    return parent ? `${categoryLabel(parent)} / ${category.title}` : category.title
}

function toggleCategory(id: number) {
    selectedIds.value = selectedIds.value.includes(id)
        ? selectedIds.value.filter(categoryId => categoryId !== id)
        : [...selectedIds.value, id]
}

function save() {
    saving.value = true
    router.put(`/subscription-plans/${props.plan.id}/categories`, { category_ids: selectedIds.value }, { onFinish: () => { saving.value = false } })
}

function changePage(page: number) {
    if (page < 1 || page > props.pagination.last_page) return
    router.get(`/subscription-plans/${props.plan.id}/categories`, {
        page,
        per_page: props.pagination.per_page,
        search: searchQuery.value,
    }, { preserveState: true })
}

function changePerPage(perPage: number) {
    router.get(`/subscription-plans/${props.plan.id}/categories`, {
        per_page: perPage,
        page: 1,
        search: searchQuery.value,
    }, { preserveState: true })
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null
function onSearchInput() {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get(`/subscription-plans/${props.plan.id}/categories`, {
            search: searchQuery.value,
            page: 1,
        }, { preserveState: true, replace: true })
    }, 400)
}

function clearSearch() {
    searchQuery.value = ''
    router.get(`/subscription-plans/${props.plan.id}/categories`, {}, { preserveState: true, replace: true })
}
</script>

<template>
    <Head :title="`${plan.name} Categories`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto max-w-8xl space-y-6 p-4 sm:p-6">
            <!-- Header Section -->
            <section class="flex flex-col gap-4 rounded-[5px] border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-900">
                <div>
                    <div class="flex items-center gap-2">
                        <FolderTree class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Categories for {{ plan.name }}</h1>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Subscribers can view documents in every selected category.</p>
                </div>
                <button :disabled="saving" @click="save" class="inline-flex items-center justify-center gap-2 rounded-[5px] bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-purple-700 disabled:opacity-50 transition-colors">
                    <Save class="h-4 w-4" />
                    {{ saving ? 'Saving...' : 'Save categories' }}
                    <span v-if="selectedIds.length" class="rounded-full bg-white/20 px-2 py-0.5 text-xs">{{ selectedIds.length }}</span>
                </button>
            </section>

            <!-- Assigned Categories Summary -->
            <section v-if="plan.categories.length" class="rounded-[5px] border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-3">Currently Assigned ({{ plan.categories.length }})</h2>
                <div class="flex flex-wrap gap-2">
                    <span v-for="cat in plan.categories" :key="cat.id" class="inline-flex items-center gap-1.5 rounded-full bg-purple-50 border border-purple-200 px-3 py-1 text-xs font-semibold text-purple-700 dark:bg-purple-950 dark:border-purple-800 dark:text-purple-300">
                        <Check class="h-3 w-3" />
                        {{ cat.title }}
                    </span>
                </div>
            </section>

            <!-- Category Selection List -->
            <section class="overflow-hidden rounded-[5px] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <!-- Search Bar -->
                <div class="border-b border-slate-100 dark:border-slate-800 px-6 py-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <p class="text-sm text-slate-500">{{ pagination.total }} categories available. Check boxes to assign to plan.</p>
                        <div class="flex items-center gap-3">
                            <p class="text-sm text-slate-500">{{ pagination.total }} categories available.</p>
                            <div class="flex items-center gap-1.5 border-l border-slate-200 dark:border-slate-700 pl-3">
                                <button
                                    type="button"
                                    @click="selectAll"
                                    class="rounded-lg bg-purple-50 dark:bg-purple-950/50 border border-purple-200 dark:border-purple-800 px-2.5 py-1 text-xs font-semibold text-purple-700 dark:text-purple-300 hover:bg-purple-100 transition-colors"
                                >
                                    Select All
                                </button>
                                <button
                                    type="button"
                                    @click="deselectAll"
                                    class="rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-200 transition-colors"
                                >
                                    Deselect All
                                </button>
                            </div>
                        </div>
                        <div class="relative w-full sm:w-64">
                            <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                            <input
                                v-model="searchQuery"
                                @input="onSearchInput"
                                type="text"
                                placeholder="Search categories..."
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
                </div>

                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    <label
                        v-for="category in categories"
                        :key="category.id"
                        class="flex cursor-pointer items-center gap-4 px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                    >
                        <input
                            type="checkbox"
                            :checked="selectedIds.includes(category.id)"
                            @change="toggleCategory(category.id)"
                            class="h-5 w-5 rounded border-slate-300 text-purple-600 focus:ring-purple-500 dark:border-slate-600 dark:bg-slate-800"
                        />
                        <span class="flex-1 font-medium text-slate-900 dark:text-white">{{ categoryLabel(category) }}</span>
                        <Check v-if="selectedIds.includes(category.id)" class="h-5 w-5 text-purple-600 flex-shrink-0" />
                    </label>
                    <p v-if="!categories.length" class="p-8 text-center text-sm text-slate-400">
                        {{ searchQuery ? 'No matching categories found.' : 'Create categories before assigning them to a plan.' }}
                    </p>
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
                            <option :value="15">15 / page</option>
                            <option :value="25">25 / page</option>
                            <option :value="50">50 / page</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button
                            :disabled="pagination.current_page <= 1"
                            @click="changePage(pagination.current_page - 1)"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:hover:bg-gray-800 transition-colors"
                        >
                            Previous
                        </button>
                        <button
                            :disabled="pagination.current_page >= pagination.last_page"
                            @click="changePage(pagination.current_page + 1)"
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
