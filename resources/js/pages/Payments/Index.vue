<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { CheckCircle2, ReceiptText, XCircle } from 'lucide-vue-next'
import DataTable from '@/components/ui/data-table/DataTable.vue'
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
    subscriptions: { data: Subscription[]; current_page: number; last_page: number; per_page: number; total: number }
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

            <DataTable
                :data="subscriptions.data"
                :pagination="{ current_page: subscriptions.current_page, last_page: subscriptions.last_page, per_page: subscriptions.per_page || 15, total: subscriptions.total }"
                :columns="['user', 'plan', 'amount', 'requested', 'status', 'action']"
                @page-change="(page) => router.get('/payments', { page }, { preserveScroll: true })"
            >
                <template #header-user>User</template>
                <template #header-plan>Plan</template>
                <template #header-amount>Amount</template>
                <template #header-requested>Requested</template>
                <template #header-status>Status</template>
                <template #header-action>Action</template>

                <template #user="{ item }">
                    <p class="font-medium text-slate-900 dark:text-white">{{ item.user.name }}</p>
                    <p class="text-xs">{{ item.user.email }}</p>
                </template>

                <template #plan="{ item }">
                    <p class="font-medium text-slate-900 dark:text-white">{{ item.plan.name }}</p>
                    <p class="text-xs">{{ item.plan.duration_days ? `${item.plan.duration_days} days` : 'Lifetime' }}</p>
                </template>

                <template #amount="{ item }">
                    <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ formatPrice(item.plan.price, item.plan.currency) }}</span>
                </template>

                <template #requested="{ item }">
                    {{ formatDate(item.created_at) }}
                </template>

                <template #status="{ item }">
                    <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', item.status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : item.status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300']">{{ item.status }}</span>
                </template>

                <template #action="{ item }">
                    <div v-if="item.status === 'pending'" class="flex justify-end gap-2">
                        <button :disabled="processingId === item.id" @click="updatePayment(item, 'approve')" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-50">
                            <CheckCircle2 class="h-3.5 w-3.5" />Approve
                        </button>
                        <button :disabled="processingId === item.id" @click="updatePayment(item, 'reject')" class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 disabled:opacity-50 dark:border-red-900 dark:hover:bg-red-950">
                            <XCircle class="h-3.5 w-3.5" />Reject
                        </button>
                    </div>
                    <span v-else class="block text-right text-xs text-slate-400">No action required</span>
                </template>

                <template #empty>No subscription payments found.</template>
            </DataTable>
        </main>
    </AppLayout>
</template>
