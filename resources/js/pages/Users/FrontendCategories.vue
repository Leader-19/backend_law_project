<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
// import { FolderTree, Search, X, Check, Trash2, ChevronLeft, ChevronRight } from '@lucide/vue'
import { FolderTree, Search, X, Check, Trash2, ChevronLeft, ChevronRight } from '@lucide/vue'

interface User {
    id: number
    name: string
    email: string
}

interface Category {
    id: number
    title: string
    description: string
    permission: string
}

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Frontend Users', href: '/frontend-users' },
    { title: 'Category Assignment', href: '#' },
]

const props = defineProps<{
    user: User
    assignedCategories: Category[]
    allCategories: Category[]
    availablePermissions: string[]
    pagination: Pagination
}>()

function initials(name: string) {
    const parts = name.trim().split(/\s+/).filter(Boolean)
    if (parts.length === 0) return '?'
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}

const categorySearch = ref('')
const selected = ref<Record<number, string[]>>({})

const filteredCategories = computed(() => {
    if (!categorySearch.value.trim()) return props.allCategories
    const query = categorySearch.value.toLowerCase()
    return props.allCategories.filter(c =>
        c.title.toLowerCase().includes(query) ||
        (c.description && c.description.toLowerCase().includes(query))
    )
})

const pageSize = 12
const currentPage = ref(1)

const totalPages = computed(() => Math.max(1, Math.ceil(filteredCategories.value.length / pageSize)))

const pagedCategories = computed(() => {
    const start = (currentPage.value - 1) * pageSize
    return filteredCategories.value.slice(start, start + pageSize)
})

watch(categorySearch, () => { currentPage.value = 1 })

function goToPage(page: number) {
    if (page < 1 || page > totalPages.value) return
    currentPage.value = page
}

function initCategory(categoryId: number) {
    if (!selected.value[categoryId]) {
        const existing = props.assignedCategories.find(c => c.id === categoryId)
        selected.value[categoryId] = existing ? existing.permission.split(',').filter(p => p.trim() !== '') : []
    }
}

function toggleCategory(categoryId: number) {
    initCategory(categoryId)
    const perms = selected.value[categoryId]
    if (perms.length > 0) {
        delete selected.value[categoryId]
    } else {
        selected.value[categoryId] = ['view']
    }
}

function togglePermission(categoryId: number, permission: string) {
    initCategory(categoryId)
    const perms = selected.value[categoryId]
    const index = perms.indexOf(permission)
    if (index > -1) {
        perms.splice(index, 1)
        if (perms.length === 0) {
            delete selected.value[categoryId]
        }
    } else {
        perms.push(permission)
    }
}

function isChecked(categoryId: number) {
    return !!selected.value[categoryId]?.length
}

function isPermissionChecked(categoryId: number, permission: string) {
    return selected.value[categoryId]?.includes(permission) || false
}

const selectedCount = computed(() => Object.keys(selected.value).length)

function saveAssignments() {
    const assignments: Array<{ category_id: number; permissions: string[] }> = []
    for (const [categoryId, perms] of Object.entries(selected.value)) {
        if (perms.length > 0) {
            assignments.push({
                category_id: Number(categoryId),
                permissions: perms,
            })
        }
    }

    if (assignments.length === 0) return

    router.post(`/frontend-users/${props.user.id}/categories`, { assignments }, {
        onSuccess: () => { selected.value = {} },
    })
}

function removeAssignment(categoryId: number) {
    router.delete(`/frontend-users/${props.user.id}/categories/${categoryId}`, {
        preserveScroll: true,
    })
}

function clearSelection() {
    selected.value = {}
}
</script>

<template>
    <Head title="Category Assignment" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- categories page model -->
        <main class="mx-auto w-full max-w-none space-y-8 p-4 pb-28 sm:p-6 lg:p-8">
            <!-- Header -->
            <header class="flex items-center gap-4 border-b border-slate-200 pb-6 dark:border-slate-800">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-slate-900 text-lg font-semibold text-white dark:bg-white dark:text-slate-900">
                    {{ initials(user.name) }}
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Category access</h1>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                        {{ user.name }} · {{ user.email }}
                    </p>
                </div>
            </header>

            <!-- Currently assigned -->
            <section v-if="assignedCategories.length">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">
                    Currently assigned ({{ assignedCategories.length }})
                </h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    <div
                        v-for="cat in assignedCategories"
                        :key="cat.id"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 py-1.5 pl-3 pr-2 dark:border-slate-700 dark:bg-slate-800"
                    >
                        <FolderTree class="h-3.5 w-3.5 text-slate-400" />
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ cat.title }}</span>
                        <span class="rounded-full bg-slate-200 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                            {{ cat.permission }}
                        </span>
                        <button
                            type="button"
                            @click="removeAssignment(cat.id)"
                            class="ml-1 text-slate-400 hover:text-red-500"
                            title="Remove assignment"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </section>

            <!-- Category picker -->
            <section>
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">
                        {{ allCategories.length }} categories
                    </h2>
                    <div class="relative w-56">
                        <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <input
                            v-model="categorySearch"
                            type="text"
                            placeholder="Search categories"
                            class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-8 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-white dark:focus:ring-white"
                        />
                        <button
                            v-if="categorySearch"
                            @click="categorySearch = ''"
                            class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div v-if="filteredCategories.length" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div
                        v-for="category in pagedCategories"
                        :key="category.id"
                        class="rounded-lg border p-3 transition-colors"
                        :class="isChecked(category.id)
                            ? 'border-slate-900 bg-slate-50 dark:border-white dark:bg-slate-800'
                            : 'border-slate-200 dark:border-slate-700'"
                    >
                        <label class="flex cursor-pointer items-start gap-3">
                            <input
                                type="checkbox"
                                :checked="isChecked(category.id)"
                                @change="toggleCategory(category.id)"
                                class="sr-only"
                            />
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border"
                                :class="isChecked(category.id)
                                    ? 'border-slate-900 bg-slate-900 dark:border-white dark:bg-white'
                                    : 'border-slate-300 dark:border-slate-600'"
                            >
                                <Check v-if="isChecked(category.id)" class="h-3.5 w-3.5 text-white dark:text-slate-900" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-1.5 font-medium text-slate-800 dark:text-slate-100">
                                    <FolderTree class="h-3.5 w-3.5 text-slate-400" /> {{ category.title }}
                                </span>
                                <span v-if="category.description" class="mt-0.5 block text-sm text-slate-500 dark:text-slate-400">
                                    {{ category.description }}
                                </span>
                            </span>
                        </label>

                        <div v-if="isChecked(category.id)" class="ml-8 mt-3 flex flex-wrap gap-x-4 gap-y-1.5 border-t border-slate-200 pt-3 dark:border-slate-700">
                            <label
                                v-for="permission in availablePermissions"
                                :key="permission"
                                class="flex cursor-pointer items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300"
                            >
                                <input
                                    type="checkbox"
                                    :checked="isPermissionChecked(category.id, permission)"
                                    @change="togglePermission(category.id, permission)"
                                    class="h-3.5 w-3.5 rounded border-slate-300 text-slate-900 focus:ring-slate-900 dark:border-slate-600"
                                />
                                <span class="capitalize">{{ permission }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <p v-else class="py-12 text-center text-sm text-slate-400">
                    {{ categorySearch ? 'No matching categories found.' : 'No categories available.' }}
                </p>

                <!-- Pagination -->
                <div v-if="filteredCategories.length" class="mt-5 flex items-center justify-between border-t border-slate-200 pt-4 dark:border-slate-800">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Page {{ currentPage }} of {{ totalPages }} · {{ filteredCategories.length }} categories
                    </p>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            :disabled="currentPage === 1"
                            @click="goToPage(currentPage - 1)"
                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:pointer-events-none disabled:opacity-40 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            <ChevronLeft class="h-4 w-4" /> Previous
                        </button>
                        <button
                            type="button"
                            :disabled="currentPage === totalPages"
                            @click="goToPage(currentPage + 1)"
                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:pointer-events-none disabled:opacity-40 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            Next <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </section>
        </main>

        <!-- Sticky action bar -->
        <div class="fixed inset-x-0 bottom-0 border-t border-slate-200 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95">
            <div class="mx-auto flex max-w-none items-center justify-end gap-3 p-4 lg:px-8">
                <button
                    v-if="selectedCount > 0"
                    type="button"
                    @click="clearSelection"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                >
                    Clear ({{ selectedCount }})
                </button>
                <button
                    type="button"
                    :disabled="selectedCount === 0"
                    @click="saveAssignments"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200"
                >
                    Save assignments
                    <span v-if="selectedCount > 0" class="rounded-full bg-white/20 px-2 py-0.5 text-xs dark:bg-slate-900/10">{{ selectedCount }}</span>
                </button>
            </div>
        </div>
    </AppLayout>
</template>
