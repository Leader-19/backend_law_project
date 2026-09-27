<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

interface UserRow {
    id: number
    name: string
    email: string
    roles: string[]
}

interface Category {
    id: number
    title: string
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: 'Category Assignment', href: '/users/categories/assign' },
]

const props = defineProps<{
    users: UserRow[]
    categories: Category[]
    userCategoryMap: Record<number, Record<number, string>>
    permissions: string[]
}>()

const selected = ref<Record<number, Record<number, string[]>>>({})

function initUserCategory(userId: number, categoryId: number) {
    if (!selected.value[userId]) {
        selected.value[userId] = {}
    }
    if (!selected.value[userId][categoryId]) {
        const existing = props.userCategoryMap[userId]?.[categoryId]
        selected.value[userId][categoryId] = existing ? existing.split(',').filter(p => p.trim() !== '') : []
    }
}

function toggleCategory(userId: number, categoryId: number) {
    initUserCategory(userId, categoryId)
    const perms = selected.value[userId][categoryId]
    if (perms.length > 0) {
        delete selected.value[userId][categoryId]
        if (Object.keys(selected.value[userId]).length === 0) {
            delete selected.value[userId]
        }
    } else {
        selected.value[userId][categoryId] = ['view']
    }
}

function togglePermission(userId: number, categoryId: number, permission: string) {
    initUserCategory(userId, categoryId)
    const perms = selected.value[userId][categoryId]
    const index = perms.indexOf(permission)
    if (index > -1) {
        perms.splice(index, 1)
        if (perms.length === 0) {
            delete selected.value[userId][categoryId]
            if (Object.keys(selected.value[userId]).length === 0) {
                delete selected.value[userId]
            }
        }
    } else {
        perms.push(permission)
    }
}

function isChecked(userId: number, categoryId: number) {
    return !!selected.value[userId]?.[categoryId]?.length
}

function isPermissionChecked(userId: number, categoryId: number, permission: string) {
    return selected.value[userId]?.[categoryId]?.includes(permission) || false
}

function saveAssignments() {
    const assignments: Array<{ user_id: number; category_id: number; permissions: string[] }> = []
    for (const [userId, categories] of Object.entries(selected.value)) {
        for (const [categoryId, perms] of Object.entries(categories)) {
            if (perms.length > 0) {
                assignments.push({
                    user_id: Number(userId),
                    category_id: Number(categoryId),
                    permissions: perms,
                })
            }
        }
    }

    if (assignments.length === 0) {
        return
    }

    router.post('/users/categories/assign', { assignments }, {
        onSuccess: () => {
            selected.value = {}
        },
    })
}

</script>

<template>
    <Head title="Category Assignment" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto max-w-8xl space-y-6 p-4 sm:p-6">
            <section class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">Category Assignment</h1>
                    <p class="mt-1 text-sm text-slate-500">Assign categories to users by checking the boxes below. Select permissions for each assignment.</p>
                </div>
                <button
                    type="button"
                    :disabled="Object.keys(selected).length === 0"
                    @click="saveAssignments"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors disabled:opacity-50"
                >
                    Save Assignments
                </button>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                        <thead class="border-b text-xs uppercase text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-800">
                            <tr>
                                <th class="px-6 py-3 sticky left-0 bg-slate-50 dark:bg-slate-800 z-10">User</th>
                                <th v-for="category in categories" :key="category.id" class="px-3 py-3 text-center min-w-[160px]">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="font-medium">{{ category.title }}</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4 sticky left-0 bg-white dark:bg-slate-900 z-10">
                                    <div class="font-medium text-slate-900 dark:text-white">{{ user.name }}</div>
                                    <div class="text-xs">{{ user.email }}</div>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        <span v-for="role in user.roles" :key="role" class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-300">{{ role }}</span>
                                    </div>
                                </td>
                                <td v-for="category in categories" :key="category.id" class="px-3 py-4 text-center">
                                    <div class="flex flex-col items-center gap-1.5">
                                        <input
                                            type="checkbox"
                                            :checked="isChecked(user.id, category.id)"
                                            @change="toggleCategory(user.id, category.id)"
                                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800"
                                        />
                                        <div v-if="isChecked(user.id, category.id)" class="flex flex-col gap-1 mt-1">
                                            <label v-for="permission in permissions" :key="permission" class="flex items-center gap-1 cursor-pointer justify-center">
                                                <input
                                                    type="checkbox"
                                                    :checked="isPermissionChecked(user.id, category.id, permission)"
                                                    @change="togglePermission(user.id, category.id, permission)"
                                                    class="h-3.5 w-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800"
                                                />
                                                <span class="text-xs text-slate-600 dark:text-slate-400">{{ permission }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!users.length">
                                <td :colspan="categories.length + 1" class="px-6 py-8 text-center text-sm text-slate-400">No users found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </AppLayout>
</template>
