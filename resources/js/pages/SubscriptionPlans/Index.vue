<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { CreditCard, Plus, Pencil, Trash2, CheckCircle2, ShieldCheck } from 'lucide-vue-next'
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
    currencies: Array<{ code: string; symbol: string; name: string }>
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

function changeSubscriptionPage(page: number) {
    if (page < 1 || page > props.subscriptions.last_page) return
    router.get('/subscription-plans', { page }, { preserveState: true, preserveScroll: true })
}
</script>

<template>
    <Head title="Subscription Plans" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto max-w-6xl space-y-8 p-4 sm:p-6">
            <!-- Header Section -->
            <section class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <CreditCard class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Subscription & Pricing Plans</h1>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Manage public-facing membership tiers, pricing currencies (USD, KHR, THB), and user subscriptions.</p>
                </div>
                <button type="button" @click="openCreateModal" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors">
                    <Plus class="h-4 w-4" />
                    New Plan
                </button>
            </section>

            <!-- Cards Grid for Subscription Plans -->
            <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div v-for="plan in plans" :key="plan.id" class="relative flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm hover:border-blue-500 transition-all">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                {{ plan.currency }} Code
                            </span>
                            <span :class="['inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium', plan.is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800']">
                                {{ plan.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <h2 class="mt-3 text-lg font-bold text-slate-900 dark:text-white">{{ plan.name }}</h2>
                        <p class="mt-1 text-xs text-slate-500 line-clamp-2">{{ plan.description || 'No description provided.' }}</p>

                        <div class="mt-4 flex items-baseline gap-1">
                            <span class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ formatPrice(plan.price, plan.currency) }}</span>
                            <span class="text-xs text-slate-500">/ {{ plan.duration_days ? `${plan.duration_days} days` : 'lifetime' }}</span>
                        </div>

                        <ul class="mt-6 space-y-2 text-xs text-slate-600 dark:text-slate-400">
                            <li v-for="(feature, idx) in plan.features" :key="idx" class="flex items-center gap-2">
                                <CheckCircle2 class="h-4 w-4 text-emerald-500 flex-shrink-0" />
                                <span>{{ feature }}</span>
                            </li>
                            <li v-if="!plan.features?.length" class="text-slate-400 italic">Standard access limits</li>
                        </ul>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <a :href="`/subscription-plans/${plan.id}/categories`" class="text-xs font-semibold text-purple-600 hover:text-purple-700">Categories</a>
                            <button type="button" @click="openEditModal(plan)" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700">
                                <Pencil class="h-3.5 w-3.5" /> Edit
                            </button>
                        </div>
                        <button type="button" @click="confirmDeletePlan(plan)" class="inline-flex items-center gap-1 text-xs font-semibold text-red-500 hover:text-red-700">
                            <Trash2 class="h-3.5 w-3.5" /> Delete
                        </button>
                    </div>
                </div>
            </section>

            <!-- User Subscriptions List -->
            <section class="rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 overflow-hidden shadow-sm">
                <div class="border-b border-slate-100 p-6 dark:border-slate-800">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Active User Subscriptions</h2>
                    <p class="text-xs text-slate-500">Recent subscriptions registered by public web users.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                        <thead class="border-b text-xs uppercase text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-800">
                            <tr>
                                <th class="px-6 py-3">User</th>
                                <th class="px-6 py-3">Plan</th>
                                <th class="px-6 py-3">Price</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Subscribed Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr v-for="sub in subscriptions.data" :key="sub.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900 dark:text-white">{{ sub.user?.name || 'User #' + sub.id }}</div>
                                    <div class="text-xs text-slate-400">{{ sub.user?.email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ sub.plan?.name }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ sub.plan ? formatPrice(sub.plan.price, sub.plan.currency) : '-' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold', sub.status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300']">
                                        {{ sub.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    {{ new Date(sub.created_at).toLocaleDateString() }}
                                </td>
                            </tr>
                            <tr v-if="!subscriptions.data?.length">
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-slate-400">No active user subscriptions found yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="subscriptions.last_page > 1" class="flex items-center justify-between border-t border-slate-100 dark:border-slate-800 px-6 py-4">
                    <span class="text-sm text-slate-500">
                        Page {{ subscriptions.current_page }} of {{ subscriptions.last_page }} ({{ subscriptions.total }} total)
                    </span>
                    <div class="flex gap-2">
                        <button
                            :disabled="subscriptions.current_page <= 1"
                            @click="changeSubscriptionPage(subscriptions.current_page - 1)"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:hover:bg-gray-800 transition-colors"
                        >
                            Previous
                        </button>
                        <button
                            :disabled="subscriptions.current_page >= subscriptions.last_page"
                            @click="changeSubscriptionPage(subscriptions.current_page + 1)"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:hover:bg-gray-800 transition-colors"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </section>

            <!-- Modals -->
            <FormModal
                :open="isModalOpen"
                :plan="selectedPlan"
                :currencies="currencies"
                @update:open="isModalOpen = $event"
            />

            <ConfirmModal
                :open="isDeleteOpen"
                title="Delete Subscription Plan"
                :description="`Are you sure you want to delete ${deletingPlan?.name}? This action cannot be undone.`"
                confirm-label="Delete"
                @confirm="deletePlan"
                @update:open="isDeleteOpen = $event"
            />
        </main>
    </AppLayout>
</template>
