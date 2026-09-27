<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Check, Eye, EyeOff, RefreshCw, Save, Shield } from 'lucide-vue-next'
import { computed, ref } from 'vue'

interface User {
    id: number
    name: string
    email: string
    roles: string[]
}

const props = defineProps<{
    user: User
    userRoles: string[]
    roles: string[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Frontend Users', href: '/frontend-users' },
    { title: 'Edit User', href: '#' },
]

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    roles: props.userRoles,
})

const showPassword = ref(false)

const initials = computed(() => {
    const parts = form.name.trim().split(/\s+/).filter(Boolean)
    if (parts.length === 0) return '?'
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

function generatePassword() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%'
    let value = ''
    for (let i = 0; i < 14; i++) {
        value += chars[Math.floor(Math.random() * chars.length)]
    }
    form.password = value
    showPassword.value = true
}

function submit() {
    form.put(`/frontend-users/${props.user.id}`, {
        onSuccess: () => {
            form.reset('password')
        },
    })
}
</script>

<template>

    <Head title="Edit Frontend User" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto w-full max-w-none p-4 pb-28 sm:p-6 lg:p-8">
            <Link href="/frontend-users"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">
                <ArrowLeft class="h-4 w-4" /> Back to frontend users
            </Link>

            <form class="mt-5" @submit.prevent="submit">
                <header
                    class="flex items-center justify-between gap-4 border-b border-slate-200 pb-6 dark:border-slate-800">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-slate-900 text-lg font-semibold text-white dark:bg-white dark:text-slate-900">
                            {{ initials }}
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Edit frontend user</h1>
                            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ props.user.email }}</p>
                        </div>
                    </div>
                    <span v-if="form.isDirty"
                        class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-400">
                        Unsaved changes
                    </span>
                </header>

                <div class="grid gap-10 py-8 lg:grid-cols-2 lg:gap-8">
                    <!-- Account details -->
                    <section class="max-w-md space-y-5">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Account details</h2>

                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Full name</label>
                            <input v-model="form.name" type="text" required
                                class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-white dark:focus:ring-white" />
                            <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Email address</label>
                            <input v-model="form.email" type="email" required
                                class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-white dark:focus:ring-white" />
                            <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-600">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-200">New
                                    password</label>
                                <button type="button" @click="generatePassword"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">
                                    <RefreshCw class="h-3.5 w-3.5" /> Generate
                                </button>
                            </div>
                            <div class="relative mt-2">
                                <input v-model="form.password" :type="showPassword ? 'text' : 'password'"
                                    class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-3 pr-10 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-white dark:focus:ring-white"
                                    placeholder="Enter a new password" />
                                <button type="button" @click="showPassword = !showPassword"
                                    class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
                                    <EyeOff v-if="showPassword" class="h-4 w-4" />
                                    <Eye v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Leave blank to keep the current
                                password.</p>
                            <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-600">{{ form.errors.password
                                }}</p>
                        </div>
                    </section>

                    <!-- Roles -->
                    <section>
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Roles</h2>
                            <span class="text-xs font-medium text-slate-400">{{ form.roles.length }} selected</span>
                        </div>

                        <div class="mt-4 space-y-2">
                            <label v-for="role in props.roles" :key="role"
                                class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors"
                                :class="form.roles.includes(role)
                                    ? 'border-slate-900 bg-slate-50 dark:border-white dark:bg-slate-800'
                                    : 'border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600'">
                                <input v-model="form.roles" :value="role" type="checkbox" class="sr-only" />
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border"
                                    :class="form.roles.includes(role)
                                        ? 'border-slate-900 bg-slate-900 dark:border-white dark:bg-white'
                                        : 'border-slate-300 dark:border-slate-600'">
                                    <Check v-if="form.roles.includes(role)"
                                        class="h-3.5 w-3.5 text-white dark:text-slate-900" />
                                </span>
                                <span
                                    class="flex items-center gap-1.5 font-medium capitalize text-slate-800 dark:text-slate-100">
                                    <Shield class="h-3.5 w-3.5 text-slate-400" /> {{ role }}
                                </span>
                            </label>
                        </div>
                        <p v-if="form.errors.roles" class="mt-2 text-sm text-red-600">{{ form.errors.roles }}</p>
                    </section>
                </div>

                <!-- Sticky action bar -->
                <div
                    class="fixed inset-x-0 bottom-0 border-t border-slate-200 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95">
                    <div class="mx-auto flex max-w-none items-center justify-end gap-3 p-4 lg:px-8">
                        <Link href="/frontend-users"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                            Cancel
                        </Link>
                        <button :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                            <Save class="h-4 w-4" /> Update user
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </AppLayout>
</template>
