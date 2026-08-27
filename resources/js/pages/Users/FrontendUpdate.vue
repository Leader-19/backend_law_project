<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

interface User {
    id: number
    name: string
    email: string
    roles: string[]
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Frontend Users', href: '/frontend-users' },
    { title: 'Edit User', href: '#' },
]

const props = defineProps<{
    user: User
    userRoles: string[]
    roles: string[]
}>()

const form = ref({
    name: props.user.name,
    email: props.user.email,
    password: '',
    roles: props.userRoles,
})

function submit() {
    router.put(`/frontend-users/${props.user.id}`, form.value, {
        onSuccess: () => {
            router.visit('/frontend-users')
        },
    })
}
</script>

<template>
    <Head title="Edit Frontend User" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto max-w-2xl space-y-6 p-4 sm:p-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                <h1 class="text-xl font-bold text-slate-900 dark:text-white mb-6">Edit Frontend User</h1>
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Name</label>
                        <input v-model="form.name" type="text" required class="w-full rounded-xl border border-slate-300 px-4 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                        <input v-model="form.email" type="email" required class="w-full rounded-xl border border-slate-300 px-4 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Password (leave blank to keep current)</label>
                        <input v-model="form.password" type="password" class="w-full rounded-xl border border-slate-300 px-4 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Roles</label>
                        <select v-model="form.roles" multiple class="w-full rounded-xl border border-slate-300 px-4 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                            <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-3 pt-4">
                        <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors">Update User</button>
                        <Link href="/frontend-users" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 transition-colors">Cancel</Link>
                    </div>
                </form>
            </section>
        </main>
    </AppLayout>
</template>