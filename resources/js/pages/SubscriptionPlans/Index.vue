<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
// import { CreditCard, Plus, Pencil, Trash2, CheckCircle2, Users, XCircle, FolderTree } from '@lucide/vue'
import { CreditCard, Plus, Pencil, Trash2, CheckCircle2, Users, XCircle, FolderTree } from '@lucide/vue'
import DataTable from '@/components/ui/data-table/DataTable.vue'
import FormModal from './FormModal.vue'
import ConfirmModal from '@/components/ConfirmModal.vue'

interface Plan {
    id: number
    name: string
    slug: string
    description: string | null
    price: number
    currency: string
    duration_days: number | null
    features: string[] | null
    max_categories: number | null
    max_documents: number | null
    max_storage_mb: number | null
    is_active: boolean
}

interface UserSubscription {
    id: number
    status: string
    starts_at: string
    ends_at: string | null
    created_at: string
    user: { id: number; name: string; email: string }
    plan: { id: number; name: string; price: number; currency: string }
}

const props = defineProps<{
    plans: Plan[]
    subscriptions: {
        data: UserSubscription[]
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
    categories: Array<{ id: number; title: string; parent_id: number | null }>
    currencies: Array<{ code: string; symbol: string; name: string }>
    subscriptionStats: {
        total_active: number
        multi_plan_users: number
        active_counts_by_user: Record<number, number>
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Subscription Plans', href: '/subscription-plans' },
]

const isModalOpen = ref(false)
const selectedPlan = ref<Plan | null>(null)

const isDeleteOpen = ref(false)
const deletingPlan = ref<Plan | null>(null)

function openCreateModal() {
    selectedPlan.value = null
    isModalOpen.value = true
}

function openEditModal(plan: Plan) {
    selectedPlan.value = plan
    isModalOpen.value = true
}

function confirmDeletePlan(plan: Plan) {
    deletingPlan.value = plan
    isDeleteOpen.value = true
}

function deletePlan() {
    if (!deletingPlan.value) return
    router.delete(`/subscription-plans/${deletingPlan.value.id}`, {
        onSuccess: () => {
            isDeleteOpen.value = false
            deletingPlan.value = null
        },
    })
}

function formatPrice(price: number | string, currency: string) {
    const symbolMap: Record<string, string> = {
        USD: '$',
        KHR: '៛',
        THB: '฿',
    }
    const symbol = symbolMap[currency] || currency
    const numericPrice = typeof price === 'number' ? price : parseFloat(String(price))
    if (currency === 'KHR') {
        return `${numericPrice.toLocaleString()} ${symbol}`
    }
    return `${symbol}${numericPrice.toFixed(2)}`
}

function initials(name: string) {
    const parts = (name ?? '').trim().split(/\s+/).filter(Boolean)
    if (parts.length === 0) return '?'
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}

const statusFilter = ref<string>('')

function applyStatusFilter(status: string) {
    statusFilter.value = status
    const params: Record<string, string> = {}
    if (status) params.status = status
    router.get('/subscription-plans', params, { preserveState: true, preserveScroll: true })
}

function changeSubscriptionPage(page: number) {
    if (page < 1 || page > props.subscriptions.last_page) return
    const params: Record<string, string | number> = { page }
    if (statusFilter.value) params.status = statusFilter.value
    router.get('/subscription-plans', params, { preserveState: true, preserveScroll: true })
}

function userPlanCount(userId: number): number {
    return props.subscriptionStats.active_counts_by_user[userId] ?? 0
}
</script>

<template>
    <Head title="Subscription Plans" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto w-full max-w-none space-y-8 p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <section class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                <div class="flex items-center gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-900 text-white dark:bg-white dark:text-slate-900">
                        <CreditCard class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Subscription plans</h1>
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                            Manage membership tiers, pricing currencies, and user subscriptions.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200"
                >
                    <Plus class="h-4 w-4" />
                    New plan
                </button>
            </section>

            <!-- Plan cards -->
            <section class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-6 transition-colors hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ plan.currency }}
                            </span>
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="plan.is_active
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                    : 'bg-slate-100 text-slate-500 dark:bg-slate-800'"
                            >
                                {{ plan.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <h2 class="mt-3 text-lg font-semibold text-slate-900 dark:text-white">{{ plan.name }}</h2>
                        <p class="mt-1 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">{{ plan.description || 'No description provided.' }}</p>

                        <div class="mt-4 flex items-baseline gap-1">
                            <span class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ formatPrice(plan.price, plan.currency) }}</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">/ {{ plan.duration_days ? `${plan.duration_days} days` : 'lifetime' }}</span>
                        </div>

                        <ul class="mt-6 space-y-2 text-xs text-slate-600 dark:text-slate-400">
                            <li v-for="(feature, idx) in plan.features" :key="idx" class="flex items-center gap-2">
                                <CheckCircle2 class="h-4 w-4 shrink-0 text-emerald-500" />
                                <span>{{ feature }}</span>
                            </li>
                            <li v-if="!plan.features?.length" class="italic text-slate-400">Standard access limits</li>
                        </ul>
                    </div>

                    <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <a
                                :href="`/subscription-plans/${plan.id}/categories`"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white"
                            >
                                <FolderTree class="h-3.5 w-3.5" /> Categories
                            </a>
                            <button
                                type="button"
                                @click="openEditModal(plan)"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white"
                            >
                                <Pencil class="h-3.5 w-3.5" /> Edit
                            </button>
                        </div>
                        <button type="button" @click="confirmDeletePlan(plan)" class="inline-flex items-center gap-1 text-xs font-semibold text-red-500 hover:text-red-700">
                            <Trash2 class="h-3.5 w-3.5" /> Delete
                        </button>
                    </div>
                </div>
            </section>

            <!-- User subscriptions -->
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 p-6 dark:border-slate-800">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">User subscriptions</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Manage and review all user subscription records.</p>
                        </div>
                        <div class="flex items-center gap-4 text-xs">
                            <div class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 dark:border-slate-700 dark:bg-slate-800">
                                <CheckCircle2 class="h-3.5 w-3.5 text-emerald-500" />
                                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ subscriptionStats.total_active }} active</span>
                            </div>
                            <div v-if="subscriptionStats.multi_plan_users > 0" class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 dark:border-slate-700 dark:bg-slate-800">
                                <Users class="h-3.5 w-3.5 text-slate-400" />
                                <span class="font-semibold text-slate-700 dark:text-slate-200">
                                    {{ subscriptionStats.multi_plan_users }} multi-plan user{{ subscriptionStats.multi_plan_users > 1 ? 's' : '' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Status filter -->
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button
                            @click="applyStatusFilter('')"
                            :class="[
                                'rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors',
                                !statusFilter
                                    ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700',
                            ]"
                        >
                            All
                        </button>
                        <button
                            v-for="s in ['active', 'pending', 'cancelled', 'expired']"
                            :key="s"
                            @click="applyStatusFilter(s)"
                            :class="[
                                'rounded-lg px-3 py-1.5 text-xs font-semibold capitalize transition-colors',
                                statusFilter === s
                                    ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700',
                            ]"
                        >
                            {{ s }}
                        </button>
                    </div>
                </div>

                <DataTable
                    :data="subscriptions.data || []"
                    :pagination="{ current_page: subscriptions.current_page, last_page: subscriptions.last_page, per_page: subscriptions.per_page, total: subscriptions.total }"
                    :columns="['user', 'plan', 'price', 'receipt', 'status', 'date', 'action']"
                    @page-change="changeSubscriptionPage"
                >
                    <template #header-user>User</template>
                    <template #header-plan>Plan</template>
                    <template #header-price>Price</template>
                    <template #header-receipt>Receipt</template>
                    <template #header-status>Status</template>
                    <template #header-date>Subscribed date</template>
                    <template #header-action>Action</template>

                    <template #user="{ item }">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white dark:bg-white dark:text-slate-900">
                                {{ initials(item.user?.name || '') }}
                            </div>
                            <div class="min-w-0">
                                <div class="truncate font-medium text-slate-900 dark:text-white">{{ item.user?.name || 'User #' + item.id }}</div>
                                <div class="truncate text-xs text-slate-500 dark:text-slate-400">{{ item.user?.email }}</div>
                                <span
                                    v-if="userPlanCount(item.user?.id) > 1"
                                    class="mt-1 inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                >
                                    {{ userPlanCount(item.user?.id) }} plans
                                </span>
                            </div>
                        </div>
                    </template>

                    <template #plan="{ item }">
                        <span class="font-medium text-slate-900 dark:text-white">{{ item.plan?.name }}</span>
                    </template>

                    <template #price="{ item }">
                        <span class="font-medium text-slate-900 dark:text-white">{{ item.plan ? formatPrice(item.plan.price, item.plan.currency) : '-' }}</span>
                    </template>

                    <template #receipt="{ item }">
                        <a v-if="item.payments?.[0]?.receipt_path" :href="`/storage/${item.payments[0].receipt_path}`" target="_blank">
                            <img :src="`/storage/${item.payments[0].receipt_path}`" alt="Payment receipt" class="h-12 w-12 rounded-md border border-slate-200 object-cover transition hover:scale-150 hover:shadow-lg dark:border-slate-700" />
                        </a>
                        <span v-else class="text-slate-400">—</span>
                    </template>

                    <template #status="{ item }">
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
                            :class="item.status === 'active'
                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'"
                        >
                            {{ item.status }}
                        </span>
                    </template>

                    <template #date="{ item }">
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ new Date(item.created_at).toLocaleDateString() }}</span>
                    </template>

                    <template #action="{ item }">
                        <div v-if="item.status === 'pending'" class="flex justify-end gap-2">
                            <button
                                @click="router.post(`/subscription-payments/${item.id}/approve`, {}, { preserveScroll: true })"
                                class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-emerald-700"
                            >
                                <CheckCircle2 class="h-3.5 w-3.5" /> Approve
                            </button>
                            <button
                                @click="router.post(`/subscription-payments/${item.id}/reject`, {}, { preserveScroll: true })"
                                class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition-colors hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-950"
                            >
                                <XCircle class="h-3.5 w-3.5" /> Reject
                            </button>
                        </div>
                        <span v-else class="block text-right text-xs text-slate-400">No action</span>
                    </template>

                    <template #empty>No active user subscriptions found yet.</template>
                </DataTable>
            </section>

            <!-- Modals -->
            <FormModal
                :open="isModalOpen"
                :plan="selectedPlan"
                :currencies="currencies"
                :categories="categories"
                @update:open="isModalOpen = $event"
            />

            <ConfirmModal
                :open="isDeleteOpen"
                title="Delete subscription plan"
                :description="`Are you sure you want to delete ${deletingPlan?.name}? This can't be undone.`"
                confirm-label="Delete"
                @confirm="deletePlan"
                @update:open="isDeleteOpen = $event"
            />
        </main>
    </AppLayout>
</template>
