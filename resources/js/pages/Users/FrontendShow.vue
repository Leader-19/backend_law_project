<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { ArrowLeft, Shield, FolderTree, Pencil, Trash2, CreditCard } from 'lucide-vue-next'
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

function initials(name: string) {
    const parts = name.trim().split(/\s+/).filter(Boolean)
    if (parts.length === 0) return '?'
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}

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
        <main class="mx-auto w-full max-w-none space-y-8 p-4 sm:p-6 lg:p-8">
            <Link href="/frontend-users" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">
                <ArrowLeft class="h-4 w-4" /> Back to frontend users
            </Link>

            <!-- Profile header -->
            <section class="border-b border-slate-200 pb-6 dark:border-slate-800">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <img v-if="user.avatar_url" :src="user.avatar_url" class="h-14 w-14 shrink-0 rounded-full object-cover" />
                        <div v-else class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-slate-900 text-lg font-semibold text-white dark:bg-white dark:text-slate-900">
                            {{ initials(user.name) }}
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">{{ user.name }}</h1>
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ user.email }}</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <span
                                    v-for="role in user.roles"
                                    :key="role"
                                    class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-medium capitalize text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                >
                                    <Shield class="h-3 w-3 text-slate-400" /> {{ role }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 sm:shrink-0">
                        <Link :href="`/frontend-users/${user.id}/edit`" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3.5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            <Pencil class="h-4 w-4" /> Edit
                        </Link>
                        <Link :href="`/frontend-users/${user.id}/categories`" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3.5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            <FolderTree class="h-4 w-4" /> Categories
                        </Link>
                        <button @click="isDeleteOpen = true" class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50">
                            <Trash2 class="h-4 w-4" /> Delete
                        </button>
                    </div>
                </div>
            </section>

            <!-- Main content + sidebar -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Sidebar: overview + subscription -->
                <aside class="space-y-6 lg:col-span-1">
                    <section class="space-y-3">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Overview</h2>
                        <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Registration source</p>
                            <p class="mt-1 text-sm font-medium capitalize text-slate-900 dark:text-white">{{ user.registration_source }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Assigned categories</p>
                            <p class="mt-1 text-sm font-medium text-slate-900 dark:text-white">{{ user.categories.length }}</p>
                        </div>
                    </section>

                    <section v-if="user.subscription">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Subscription</h2>
                        <div class="mt-3 rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                            <div class="flex items-start gap-3">
                                <CreditCard class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                                <div class="min-w-0">
                                    <p class="font-medium text-slate-900 dark:text-white">{{ user.subscription.plan?.name ?? 'Unknown plan' }}</p>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        {{ user.subscription.plan?.max_categories ?? 'Unlimited' }} categories ·
                                        {{ user.subscription.plan?.max_documents ?? 'Unlimited' }} documents
                                    </p>
                                    <span class="mt-3 inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-medium capitalize text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                        {{ user.subscription.status }}
                                    </span>
                                    <p v-if="user.subscription.ends_at" class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                                        Expires {{ user.subscription.ends_at }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>
                </aside>

                <!-- Main: assigned categories -->
                <section class="lg:col-span-2">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Assigned categories</h2>
                        <span class="text-xs font-medium text-slate-400">{{ user.categories.length }} total</span>
                    </div>
                    <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <div
                            v-for="cat in user.categories"
                            :key="cat.id"
                            class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-4 py-3 dark:border-slate-800"
                        >
                            <span class="flex min-w-0 items-center gap-2 font-medium text-slate-800 dark:text-slate-100">
                                <FolderTree class="h-4 w-4 shrink-0 text-slate-400" />
                                <span class="truncate">{{ cat.title }}</span>
                            </span>
                            <span class="shrink-0 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-medium capitalize text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ cat.permission }}
                            </span>
                        </div>
                        <p v-if="!user.categories.length" class="col-span-full rounded-lg border border-slate-200 px-4 py-12 text-center text-sm text-slate-400 dark:border-slate-800">
                            No categories assigned yet.
                        </p>
                    </div>
                </section>
            </div>

            <ConfirmModal
                :open="isDeleteOpen"
                title="Delete user"
                :description="`Are you sure you want to delete ${user.name}? This can't be undone.`"
                confirm-label="Delete user"
                @confirm="deleteUser"
                @update:open="isDeleteOpen = $event"
            />
        </main>
    </AppLayout>
</template>
