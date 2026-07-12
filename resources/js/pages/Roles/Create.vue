<script setup lang="ts">
import PermissionSelector from '@/components/PermissionSelector.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, KeyRound, Save, ShieldCheck } from 'lucide-vue-next'
import { route } from 'ziggy-js'

const props = defineProps<{ permissions: string[] }>()
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Create role', href: '/roles' }]
const form = useForm({ name: '', permissions: [] as string[] })
</script>

<template>
    <Head title="Create Role" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto max-w-5xl p-4 sm:p-6">
            <Link :href="route('roles.index')" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-blue-600 dark:text-slate-300">
                <ArrowLeft class="h-4 w-4" /> Back to roles
            </Link>
            <form class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm dark:border-slate-700 dark:bg-slate-950" @submit.prevent="form.post(route('roles.store'))">
                <div class="border-b border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
                    <div class="flex items-start gap-4">
                        <div class="rounded-xl bg-blue-100 p-3 text-blue-700 dark:bg-blue-950 dark:text-blue-300"><ShieldCheck class="h-6 w-6" /></div>
                        <div><h1 class="text-xl font-bold text-slate-900 dark:text-white">Create a role</h1><p class="mt-1 text-sm text-slate-500">Name the role and choose exactly what it can access.</p></div>
                    </div>
                    <div class="mt-6 max-w-xl">
                        <label for="role-name" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Role name</label>
                        <div class="relative mt-2"><KeyRound class="absolute left-3 top-3 h-4 w-4 text-slate-400" /><input id="role-name" v-model="form.name" class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-800" placeholder="Example: Document manager" /></div>
                        <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>
                </div>
                <div class="p-6"><div class="mb-4"><h2 class="font-semibold text-slate-900 dark:text-white">Permissions</h2><p class="text-sm text-slate-500">Select permissions by area. Use the group action for faster selection.</p></div><PermissionSelector v-model="form.permissions" :permissions="props.permissions" /><p v-if="form.errors.permissions" class="mt-3 text-sm text-red-600">{{ form.errors.permissions }}</p></div>
                <div class="flex justify-end gap-3 border-t border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900"><Link :href="route('roles.index')" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300">Cancel</Link><button :disabled="form.processing" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"><Save class="h-4 w-4" /> Create role</button></div>
            </form>
        </main>
    </AppLayout>
</template>
