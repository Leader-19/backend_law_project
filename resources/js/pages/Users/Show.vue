<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft, Mail, Pencil, UserRound } from 'lucide-vue-next'
import { route } from 'ziggy-js'

const props = defineProps<{ user: { id: number; name: string; email: string; roles?: { name: string }[] } }>()
const breadcrumbs: BreadcrumbItem[] = [{ title: 'User details', href: '/users' }]
</script>

<template>
    <Head title="User Details" />
    <AppLayout :breadcrumbs="breadcrumbs"><main class="mx-auto max-w-3xl p-4 sm:p-6"><div class="flex items-center justify-between gap-3"><Link :href="route('users.index')" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-blue-600 dark:text-slate-300"><ArrowLeft class="h-4 w-4" /> Back to users</Link><Link :href="route('users.edit', props.user.id)" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"><Pencil class="h-4 w-4" /> Edit user</Link></div><section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"><div class="bg-gradient-to-r from-blue-700 to-indigo-700 p-6 text-white"><div class="flex items-center gap-4"><div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/15 text-xl font-bold">{{ props.user.name.charAt(0).toUpperCase() }}</div><div><p class="text-sm text-blue-100">User profile</p><h1 class="text-2xl font-bold">{{ props.user.name }}</h1></div></div></div><div class="grid gap-6 p-6 sm:grid-cols-2"><div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800"><div class="flex items-center gap-2 text-sm text-slate-500"><Mail class="h-4 w-4" /> Email address</div><p class="mt-2 break-all font-medium text-slate-900 dark:text-white">{{ props.user.email }}</p></div><div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800"><div class="flex items-center gap-2 text-sm text-slate-500"><UserRound class="h-4 w-4" /> Assigned roles</div><div v-if="props.user.roles?.length" class="mt-2 flex flex-wrap gap-2"><span v-for="role in props.user.roles" :key="role.name" class="rounded-full bg-blue-100 px-2.5 py-1 text-sm font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-300">{{ role.name }}</span></div><p v-else class="mt-2 text-sm text-slate-500">No role assigned.</p></div></div></section></main></AppLayout>
</template>
