<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import ConfirmModal from '@/components/ConfirmModal.vue'
import { ref } from 'vue'

interface CategoryAssignment {
    id: number
    title: string
    permission: string
}

interface UserRow {
    id: number
    name: string
    email: string
    roles: string[]
    categories: CategoryAssignment[]
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: 'Categories', href: '/users/categories' },
]

const props = defineProps<{
    users: UserRow[]
    pagination: {
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
    filters: { search: string; per_page: number }
}>()

const search = ref(props.filters.search)

function go(page: number) {
    router.get(route('users.categories.index'), {
        search: search.value,
        per_page: props.filters.per_page,
        page,
    }, { preserveScroll: true })
}

function assignCategory(userId: number) {
    router.get(route('categories.permissions.index', userId))
}

const isRemoveOpen = ref(false)
const removingUserId = ref<number | null>(null)
const removingCategory = ref<CategoryAssignment | null>(null)

function removeCategory(userId: number, category: CategoryAssignment) {
    removingUserId.value = userId
    removingCategory.value = category
    isRemoveOpen.value = true
}

function confirmRemoveCategory() {
    if (removingUserId.value === null || !removingCategory.value) return
    router.delete(route('categories.permissions.destroy', [removingCategory.value.id, removingUserId.value]), { preserveScroll: true })
    isRemoveOpen.value = false
    removingUserId.value = null
    removingCategory.value = null
}
</script>

<template>
    <Head title="User Categories" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">User Categories</h1>
                        <p class="mt-1 text-sm text-slate-500">Manage which categories each user can access.</p>
                    </div>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search users..."
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                    />
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                        <thead class="border-b text-xs uppercase text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-800">
                            <tr>
                                <th class="px-6 py-3">User</th>
                                <th class="px-6 py-3">Roles</th>
                                <th class="px-6 py-3">Categories</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900 dark:text-white">{{ user.name }}</div>
                                    <div class="text-xs">{{ user.email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="role in user.roles" :key="role" class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-300">{{ role }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="cat in user.categories" :key="cat.id" class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            {{ cat.title }}
                                            <span class="text-slate-400">({{ cat.permission }})</span>
                                            <button type="button" class="text-red-500 hover:text-red-700 ml-1" @click="removeCategory(user.id, cat)">×</button>
                                        </span>
                                        <span v-if="!user.categories.length" class="text-xs text-slate-400">No categories assigned</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700" @click="assignCategory(user.id)">
                                        Assign Categories
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!users.length">
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-slate-400">No users found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between border-t border-slate-200 px-6 py-4 dark:border-slate-700 text-sm">
                    <span class="text-slate-500">{{ pagination.total }} users</span>
                    <div class="flex gap-2">
                        <button type="button" :disabled="pagination.current_page <= 1" @click="go(pagination.current_page - 1)" class="rounded-lg border border-slate-300 px-3 py-1.5 disabled:opacity-50 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Previous</button>
                        <span class="px-3 py-1.5 text-slate-600 dark:text-slate-300">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
                        <button type="button" :disabled="pagination.current_page >= pagination.last_page" @click="go(pagination.current_page + 1)" class="rounded-lg border border-slate-300 px-3 py-1.5 disabled:opacity-50 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Next</button>
                    </div>
                </div>
            </section>

            <ConfirmModal
                :open="isRemoveOpen"
                title="Remove Category"
                :description="`Are you sure you want to remove ${removingCategory?.title} from this user?`"
                confirm-label="Remove"
                @confirm="confirmRemoveCategory"
                @update:open="isRemoveOpen = $event"
            />
        </main>
    </AppLayout>
</template>
