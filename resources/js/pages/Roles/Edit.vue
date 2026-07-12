<script setup lang="ts">
import PermissionSelector from '@/components/PermissionSelector.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Pencil, Save, ShieldCheck } from 'lucide-vue-next'
import { route } from 'ziggy-js'

const props = defineProps<{ role: { id: number; name: string }; permissions: string[]; rolePermissions: string[] }>()
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Edit role', href: '/roles' }]
const form = useForm({ name: props.role.name, permissions: props.rolePermissions ?? [] })
</script>

<template>
    <Head title="Edit Role" />
    <AppLayout :breadcrumbs="breadcrumbs"><main class="mx-auto max-w-5xl p-4 sm:p-6"><Link :href="route('roles.index')" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-blue-600 dark:text-slate-300"><ArrowLeft class="h-4 w-4" /> Back to roles</Link><form class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm dark:border-slate-700 dark:bg-slate-950" @submit.prevent="form.put(route('roles.update', props.role.id))"><div class="border-b border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900"><div class="flex items-start gap-4"><div class="rounded-xl bg-amber-100 p-3 text-amber-700 dark:bg-amber-950 dark:text-amber-300"><Pencil class="h-6 w-6" /></div><div><h1 class="text-xl font-bold text-slate-900 dark:text-white">Edit role</h1><p class="mt-1 text-sm text-slate-500">Update the role name and permissions.</p></div></div><div class="mt-6 max-w-xl"><label for="role-name" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Role name</label><input id="role-name" v-model="form.name" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-800" /><p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p></div></div><div class="p-6"><div class="mb-4 flex items-center gap-2"><ShieldCheck class="h-5 w-5 text-blue-600" /><div><h2 class="font-semibold text-slate-900 dark:text-white">Permissions</h2><p class="text-sm text-slate-500">{{ form.permissions.length }} selected</p></div></div><PermissionSelector v-model="form.permissions" :permissions="props.permissions" /><p v-if="form.errors.permissions" class="mt-3 text-sm text-red-600">{{ form.errors.permissions }}</p></div><div class="flex justify-end gap-3 border-t border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900"><Link :href="route('roles.index')" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300">Cancel</Link><button :disabled="form.processing" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"><Save class="h-4 w-4" /> Save changes</button></div></form></main></AppLayout>
</template>
