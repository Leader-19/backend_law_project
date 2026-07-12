<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft, Pencil, ShieldCheck } from 'lucide-vue-next'
import { route } from 'ziggy-js'

const props = defineProps<{ role: { id: number; name: string }; rolePermissions: string[] }>()
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Role details', href: '/roles' }]
</script>

<template>
    <Head title="Role Details" />
    <AppLayout :breadcrumbs="breadcrumbs"><main class="mx-auto max-w-4xl p-4 sm:p-6"><div class="flex items-center justify-between gap-3"><Link :href="route('roles.index')" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-blue-600 dark:text-slate-300"><ArrowLeft class="h-4 w-4" /> Back to roles</Link><Link :href="route('roles.edit', props.role.id)" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"><Pencil class="h-4 w-4" /> Edit role</Link></div><section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"><div class="bg-gradient-to-r from-slate-800 to-slate-700 p-6 text-white"><div class="flex items-center gap-4"><div class="rounded-xl bg-white/15 p-3"><ShieldCheck class="h-7 w-7" /></div><div><p class="text-sm text-slate-300">Role</p><h1 class="text-2xl font-bold capitalize">{{ props.role.name }}</h1><p class="mt-1 text-sm text-slate-300">{{ props.rolePermissions.length }} permissions assigned</p></div></div></div><div class="p-6"><h2 class="font-semibold text-slate-900 dark:text-white">Assigned permissions</h2><p class="mt-1 text-sm text-slate-500">This role grants the following capabilities.</p><div v-if="props.rolePermissions.length" class="mt-5 flex flex-wrap gap-2"><span v-for="permission in props.rolePermissions" :key="permission" class="rounded-full bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-300">{{ permission }}</span></div><p v-else class="mt-5 rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">No permissions have been assigned to this role.</p></div></section></main></AppLayout>
</template>
