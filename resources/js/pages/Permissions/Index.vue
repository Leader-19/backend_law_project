<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { can } from '@/lib/can'
import { type BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { CheckCircle2, RefreshCw, ShieldCheck } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { route } from 'ziggy-js'

interface Permission { id: number; name: string; guard_name: string; roles_count: number }
const props = defineProps<{ permissions: Permission[] }>()
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Permissions', href: '/permissions' }]
const search = ref('')
const scanning = ref(false)
const notice = ref('')
const visiblePermissions = computed(() => props.permissions.filter((permission) => permission.name.toLowerCase().includes(search.value.toLowerCase())))

function scanRoutes() {
    scanning.value = true
    router.post(route('permissions.scan'), {}, {
        preserveScroll: true,
        onSuccess: () => { notice.value = 'Route scan completed. Admin has been synchronized with every permission.' },
        onFinish: () => { scanning.value = false },
    })
}
</script>

<template>
    <Head title="Permissions" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto max-w-6xl p-4 sm:p-6">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-col gap-4 bg-gradient-to-r from-slate-800 to-slate-700 p-6 text-white sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-4"><div class="rounded-xl bg-white/15 p-3"><ShieldCheck class="h-7 w-7" /></div><div><h1 class="text-2xl font-bold">Permissions</h1><p class="mt-1 text-sm text-slate-300">Permissions are created from named application routes.</p></div></div>
                    <button v-if="can('roles.edit')" :disabled="scanning" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-100 disabled:opacity-60" @click="scanRoutes"><RefreshCw :class="['h-4 w-4', { 'animate-spin': scanning }]" /> {{ scanning ? 'Scanning…' : 'Scan routes' }}</button>
                </div>
                <div class="p-6">
                    <div v-if="notice" class="mb-5 flex items-center gap-2 rounded-xl bg-green-50 p-3 text-sm text-green-700 dark:bg-green-950 dark:text-green-300"><CheckCircle2 class="h-5 w-5" /> {{ notice }}</div>
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm text-slate-500">{{ props.permissions.length }} permissions. Scanning adds missing permissions and gives all permissions to Admin.</p><input v-model="search" type="search" placeholder="Search permissions…" class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 dark:border-slate-600 dark:bg-slate-800" /></div>
                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-4 py-3">Permission</th><th class="px-4 py-3">Guard</th><th class="px-4 py-3 text-right">Roles using it</th></tr></thead><tbody><tr v-for="permission in visiblePermissions" :key="permission.id" class="border-t border-slate-100 dark:border-slate-800"><td class="px-4 py-3"><span class="rounded-full bg-blue-50 px-2.5 py-1 font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-300">{{ permission.name }}</span></td><td class="px-4 py-3 text-slate-500">{{ permission.guard_name }}</td><td class="px-4 py-3 text-right font-medium text-slate-700 dark:text-slate-200">{{ permission.roles_count }}</td></tr><tr v-if="visiblePermissions.length === 0"><td colspan="3" class="px-4 py-8 text-center text-slate-500">No permissions found.</td></tr></tbody></table></div>
                </div>
            </section>
        </main>
    </AppLayout>
</template>
