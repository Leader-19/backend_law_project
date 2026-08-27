<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { CheckCircle2, ReceiptText, XCircle } from 'lucide-vue-next'
import { ref } from 'vue'

interface Subscription {
    id: number
    status: 'pending' | 'active' | 'cancelled'
    starts_at: string | null
    ends_at: string | null
    created_at: string
    user: { id: number; name: string; email: string }
    plan: { id: number; name: string; price: number | string; currency: string; duration_days: number | null }
}

const props = defineProps<{
    subscriptions: { data: Subscription[]; current_page: number; last_page: number; total: number }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Payments', href: '/payments' },
]

const processingId = ref<number | null>(null)

function updatePayment(subscription: Subscription, action: 'approve' | 'reject') {
    processingId.value = subscription.id
    router.post(`/payments/${subscription.id}/${action}`, {}, {
        preserveScroll: true,
        onFinish: () => { processingId.value = null },
    })
}

function formatPrice(price: number | string, currency: string) {
    const amount = Number(price)
    const symbol = { USD: '$', KHR: '៛', THB: '฿' }[currency] ?? `${currency} `
    return currency === 'KHR' ? `${amount.toLocaleString()} ${symbol}` : `${symbol}${amount.toFixed(2)}`
}

function formatDate(value: string | null) {
    return value ? new Date(value).toLocaleDateString() : '—'
}
</script>

<template>
    <Head title="Payments" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    <div class="rounded-xl bg-blue-50 p-3 text-blue-600 dark:bg-blue-950 dark:text-blue-300"><ReceiptText class="h-6 w-6" /></div>
                    <div>
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">User Payments</h1>
                        <p class="mt-1 text-sm text-slate-500">Review subscription payment requests and activate approved plans.</p>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                        <thead class="border-b bg-slate-50 text-xs uppercase text-slate-700 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-300">
                            <tr><th class="px-6 py-3">User</th><th class="px-6 py-3">Plan</th><th class="px-6 py-3">Amount</th><th class="px-6 py-3">Requested</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Action</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr v-for="subscription in subscriptions.data" :key="subscription.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-6 py-4"><p class="font-medium text-slate-900 dark:text-white">{{ subscription.user.name }}</p><p class="text-xs">{{ subscription.user.email }}</p></td>
                                <td class="px-6 py-4"><p class="font-medium text-slate-900 dark:text-white">{{ subscription.plan.name }}</p><p class="text-xs">{{ subscription.plan.duration_days ? `${subscription.plan.duration_days} days` : 'Lifetime' }}</p></td>
                                <td class="px-6 py-4 font-medium text-emerald-600 dark:text-emerald-400">{{ formatPrice(subscription.plan.price, subscription.plan.currency) }}</td>
                                <td class="px-6 py-4">{{ formatDate(subscription.created_at) }}</td>
                                <td class="px-6 py-4"><span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', subscription.status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : subscription.status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300']">{{ subscription.status }}</span></td>
                                <td class="px-6 py-4"><div v-if="subscription.status === 'pending'" class="flex justify-end gap-2"><button :disabled="processingId === subscription.id" @click="updatePayment(subscription, 'approve')" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"><CheckCircle2 class="h-3.5 w-3.5" />Approve</button><button :disabled="processingId === subscription.id" @click="updatePayment(subscription, 'reject')" class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 disabled:opacity-50 dark:border-red-900 dark:hover:bg-red-950"><XCircle class="h-3.5 w-3.5" />Reject</button></div><span v-else class="block text-right text-xs text-slate-400">No action required</span></td>
                            </tr>
                            <tr v-if="!subscriptions.data.length"><td colspan="6" class="px-6 py-10 text-center text-sm text-slate-400">No subscription payments found.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="subscriptions.last_page > 1" class="flex items-center justify-between border-t p-4 dark:border-slate-800"><span class="text-sm text-slate-500">{{ subscriptions.total }} payments</span><div class="flex gap-2"><button :disabled="subscriptions.current_page === 1" @click="router.get('/payments', { page: subscriptions.current_page - 1 }, { preserveScroll: true })" class="rounded-lg border px-3 py-1.5 text-sm disabled:opacity-50 dark:border-slate-700">Previous</button><button :disabled="subscriptions.current_page === subscriptions.last_page" @click="router.get('/payments', { page: subscriptions.current_page + 1 }, { preserveScroll: true })" class="rounded-lg border px-3 py-1.5 text-sm disabled:opacity-50 dark:border-slate-700">Next</button></div></div>
            </section>
        </main>
    </AppLayout>
</template>
