<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import ConfirmModal from '@/components/ConfirmModal.vue'

interface User {
    id: number
    name: string
    email: string
    avatar_url?: string
    roles: string[]
    registration_source: string
    categories: Array<{ id: number; title: string; permission: string }>
    subscription: {
        id: number
        status: string
        starts_at: string
        ends_at: string | null
        plan: {
            id: number
            name: string
            slug: string
            price: number
            currency: string
            max_categories: number | null
            max_documents: number | null
            max_storage_mb: number | null
        } | null
    } | null
    created_at: string
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Frontend Users', href: '/frontend-users' },
    { title: 'User Details', href: '#' },
]

const props = defineProps<{ user: User }>()

const isDeleteOpen = ref(false)

function deleteUser() {
    router.delete(`/frontend-users/${props.user.id}`, {
        onSuccess: () => {
            router.visit('/frontend-users')
        },
    })
}
</script>

<template>
    <Head :title="`User: ${user.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto max-w-4xl space-y-6 p-4 sm:p-6">
            <section class="flex items-center gap-4">
                <Link href="/frontend-users" class="text-sm text-blue-600 hover:text-blue-700">&larr; Back to Frontend Users</Link>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                <div class="flex items-center gap-4 mb-6">
                    <img v-if="user.avatar_url" :src="user.avatar_url" class="h-16 w-16 rounded-full object-cover shadow-sm" />
                    <div v-else class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xl shadow-sm">
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ user.name }}</h1>
                        <p class="text-sm text-slate-500">{{ user.email }}</p>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <span v-for="role in user.roles" :key="role" class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                {{ role }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4">
                        <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Registration Source</p>
                        <p class="text-sm font-medium text-slate-900 dark:text-white capitalize">{{ user.registration_source }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4">
                        <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Category Limit</p>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ user.subscription?.plan?.max_categories || 'Unlimited' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4">
                        <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Document Limit</p>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ user.subscription?.plan?.max_documents || 'Unlimited' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4">
                        <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Current Plan</p>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ user.subscription?.plan?.name || 'No plan' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4">
                        <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Plan Status</p>
                        <p class="text-sm font-medium text-slate-900 dark:text-white capitalize">{{ user.subscription?.status || 'N/A' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4">
                        <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Assigned Categories</p>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ user.categories.length }}</p>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-800 flex flex-wrap gap-3">
                    <Link :href="`/frontend-users/${user.id}/categories`" class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Manage Categories
                    </Link>
                    <button @click="isDeleteOpen = true" class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition-colors">
                        Delete User
                    </button>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 overflow-hidden shadow-sm">
                <div class="border-b border-slate-100 p-6 dark:border-slate-800">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Assigned Categories</h2>
                    <p class="text-xs text-slate-500">Categories this user has access to.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                        <thead class="border-b text-xs uppercase text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-800">
                            <tr>
                                <th class="px-6 py-3">Category</th>
                                <th class="px-6 py-3">Permission</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr v-for="cat in user.categories" :key="cat.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">{{ cat.title }}</td>
                                <td class="px-6 py-4">{{ cat.permission }}</td>
                            </tr>
                            <tr v-if="!user.categories.length">
                                <td colspan="2" class="px-6 py-8 text-center text-sm text-slate-400">No categories assigned yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <ConfirmModal
                :open="isDeleteOpen"
                title="Delete User"
                :description="`Are you sure you want to delete ${user.name}? This action cannot be undone.`"
                confirm-label="Delete User"
                @confirm="deleteUser"
                @update:open="isDeleteOpen = $event"
            />
        </main>
    </AppLayout>
</template>
