<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { FolderTree, Search, X, CheckCircle2, Trash2 } from 'lucide-vue-next'

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
        <main class="mx-auto max-w-8xl space-y-6 p-4 sm:p-6">
            <!-- Header Section -->
            <section class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 rounded-[5px] border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <FolderTree class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Category Assignment</h1>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        Assign categories to <strong class="text-slate-700 dark:text-slate-300">{{ user.name }}</strong> ({{ user.email }}) by checking the boxes below.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        v-if="selectedCount > 0"
                        type="button"
                        @click="clearSelection"
                        class="inline-flex items-center gap-2 rounded-[5px] border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800"
                    >
                        <X class="h-4 w-4" />
                        Clear ({{ selectedCount }})
                    </button>
                    <button
                        type="button"
                        :disabled="selectedCount === 0"
                        @click="saveAssignments"
                        class="inline-flex items-center gap-2 rounded-[5px] bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors disabled:opacity-50"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        Save Assignments
                        <span v-if="selectedCount > 0" class="rounded-full bg-white/20 px-2 py-0.5 text-xs">{{ selectedCount }}</span>
                    </button>
                </div>
            </section>

            <!-- Currently Assigned Categories -->
            <section v-if="assignedCategories.length" class="rounded-[5px] border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Currently Assigned Categories ({{ pagination.total }})</h2>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="cat in assignedCategories"
                        :key="cat.id"
                        class="inline-flex items-center gap-2 rounded-[5px] bg-emerald-50 border border-emerald-200 px-3 py-2 dark:bg-emerald-950 dark:border-emerald-800"
                    >
                        <FolderTree class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ cat.title }}</span>
                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                            {{ cat.permission }}
                        </span>
                    </div>
                </div>
            </section>

            <!-- Category Selection Matrix -->
            <section class="overflow-hidden rounded-[5px] border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                <!-- Search Bar -->
                <div class="border-b border-slate-100 dark:border-slate-800 px-6 py-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <p class="text-sm text-slate-500">{{ allCategories.length }} categories available. Check boxes to assign permissions.</p>
                        <div class="relative w-full sm:w-64">
                            <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                            <input
                                v-model="categorySearch"
                                type="text"
                                placeholder="Search categories..."
                                class="w-full rounded-[5px] border border-slate-300 pl-9 pr-9 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                            <button
                                v-if="categorySearch"
                                @click="categorySearch = ''"
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
                                <th class="px-6 py-3 sticky left-0 bg-slate-50 dark:bg-slate-800 z-10">User</th>
                                <th v-for="category in filteredCategories" :key="category.id" class="px-3 py-3 text-center min-w-[160px]">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="font-medium">{{ category.title }}</span>
                                        <span v-if="category.description" class="text-[10px] text-slate-400 normal-case">{{ category.description }}</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4 sticky left-0 bg-white dark:bg-slate-900 z-10">
                                    <div class="font-semibold text-slate-900 dark:text-white">{{ user.name }}</div>
                                    <div class="text-xs text-slate-400">{{ user.email }}</div>
                                </td>
                                <td v-for="category in filteredCategories" :key="category.id" class="px-3 py-4 text-center">
                                    <div class="flex flex-col items-center gap-1.5">
                                        <input
                                            type="checkbox"
                                            :checked="isChecked(category.id)"
                                            @change="toggleCategory(category.id)"
                                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800"
                                        />
                                        <div v-if="isChecked(category.id)" class="flex flex-col gap-1 mt-1">
                                            <label v-for="permission in availablePermissions" :key="permission" class="flex items-center gap-1 cursor-pointer justify-center">
                                                <input
                                                    type="checkbox"
                                                    :checked="isPermissionChecked(category.id, permission)"
                                                    @change="togglePermission(category.id, permission)"
                                                    class="h-3.5 w-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800"
                                                />
                                                <span class="text-xs text-slate-600 dark:text-slate-400">{{ permission }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!filteredCategories.length">
                                <td :colspan="filteredCategories.length + 1" class="px-6 py-12 text-center text-sm text-slate-400">
                                    {{ categorySearch ? 'No matching categories found.' : 'No categories available.' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </AppLayout>
</template>
