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
        <main class="mx-auto w-full max-w-4xl space-y-6 p-4 pb-28 sm:p-6">
            <!-- Header -->
            <section class="flex items-center gap-4 border-b border-slate-200 pb-6 dark:border-slate-800">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-900 text-white dark:bg-white dark:text-slate-900">
                    <FolderTree class="h-5 w-5" />
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Categories for {{ plan.name }}</h1>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Subscribers can view documents in every selected category.</p>
                </div>
            </section>

            <!-- Currently assigned -->
            <section v-if="plan.categories.length">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Currently assigned ({{ plan.categories.length }})</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span v-for="cat in plan.categories" :key="cat.id"
                        class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <Check class="h-3 w-3 text-slate-400" />
                        {{ cat.title }}
                    </span>
                </div>
            </section>

            <!-- Category picker -->
            <section>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">{{ pagination.total }} categories</h2>
                        <div class="flex items-center gap-1.5 border-l border-slate-200 pl-3 text-xs dark:border-slate-700">
                            <button type="button" @click="selectAll" class="font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">Select all</button>
                            <span class="text-slate-300 dark:text-slate-600">|</span>
                            <button type="button" @click="deselectAll" class="font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">Deselect all</button>
                        </div>
                    </div>
                    <div class="relative w-full sm:w-64">
                        <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <input v-model="searchQuery" @input="onSearchInput" type="text" placeholder="Search categories"
                            class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-9 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-white dark:focus:ring-white" />
                        <button v-if="searchQuery" @click="clearSearch" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div class="mt-4 divide-y divide-slate-200 rounded-lg border border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                    <label v-for="category in categories" :key="category.id"
                        class="flex cursor-pointer items-center gap-3 px-4 py-3.5 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <input type="checkbox" :checked="selectedIds.includes(category.id)" @change="toggleCategory(category.id)" class="sr-only" />
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded border"
                            :class="selectedIds.includes(category.id) ? 'border-slate-900 bg-slate-900 dark:border-white dark:bg-white' : 'border-slate-300 dark:border-slate-600'">
                            <Check v-if="selectedIds.includes(category.id)" class="h-3.5 w-3.5 text-white dark:text-slate-900" />
                        </span>
                        <span class="flex-1 font-medium text-slate-800 dark:text-slate-100">{{ categoryLabel(category) }}</span>
                    </label>
                    <p v-if="!categories.length" class="p-8 text-center text-sm text-slate-400">
                        {{ searchQuery ? 'No matching categories found.' : 'Create categories before assigning them to a plan.' }}
                    </p>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.last_page > 1" class="mt-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-slate-500 dark:text-slate-400">
                            Page {{ pagination.current_page }} of {{ pagination.last_page }} ({{ pagination.total }} total)
                        </span>
                        <select :value="pagination.per_page" @change="changePerPage(Number(($event.target as HTMLSelectElement).value))"
                            class="rounded-lg border border-slate-200 px-2 py-1 text-xs outline-none focus:border-slate-900 dark:border-slate-700 dark:bg-slate-900">
                            <option :value="10">10 / page</option>
                            <option :value="15">15 / page</option>
                            <option :value="25">25 / page</option>
                            <option :value="50">50 / page</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button :disabled="pagination.current_page <= 1" @click="changePage(pagination.current_page - 1)"
                            class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            Previous
                        </button>
                        <button :disabled="pagination.current_page >= pagination.last_page" @click="changePage(pagination.current_page + 1)"
                            class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            Next
                        </button>
                    </div>
                </div>
            </section>
        </main>

        <!-- Sticky action bar -->
        <div class="fixed inset-x-0 bottom-0 border-t border-slate-200 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95">
            <div class="mx-auto flex max-w-4xl items-center justify-end gap-3 p-4">
                <button :disabled="saving" @click="save"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                    <Save class="h-4 w-4" />
                    {{ saving ? 'Saving…' : 'Save categories' }}
                    <span v-if="selectedIds.length" class="rounded-full bg-white/20 px-2 py-0.5 text-xs dark:bg-slate-900/10">{{ selectedIds.length }}</span>
                </button>
            </div>
        </div>
    </AppLayout>
</template>
