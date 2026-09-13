<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
const props = defineProps<{ subscription: any; usage: { used: number; limit: number | null; remaining: number | null; percentage: number; at_limit: boolean }; payments: any }>();
const openPortal = () => router.post('/billing/portal');
</script>

<template>
  <Head title="Manage subscription" />
  <AppLayout :breadcrumbs="[{ title: 'Subscription', href: '/subscription' }]">
    <main class="mx-auto max-w-5xl space-y-6 px-4 py-10 sm:px-8">
      <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-start"><div><p class="text-sm font-semibold text-indigo-600">CURRENT PLAN</p><h1 class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white">{{ subscription.plan.name }}</h1><p class="mt-2 text-sm text-slate-500" v-if="subscription.ends_at">Renews or expires {{ new Date(subscription.ends_at).toLocaleDateString() }}</p></div><div class="flex gap-3"><button v-if="subscription.provider === 'stripe'" @click="openPortal" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold dark:border-slate-600">Manage payment</button><Link href="/pricing" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white">Upgrade plan</Link></div></div>
        <div class="mt-7 rounded-xl bg-slate-50 p-5 dark:bg-slate-800/60"><div class="flex justify-between text-sm font-semibold text-slate-700 dark:text-slate-200"><span>Document usage</span><span>{{ usage.used }} / {{ usage.limit ?? '∞' }} documents</span></div><div class="mt-3 h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"><div class="h-full rounded-full" :class="usage.at_limit ? 'bg-rose-500' : 'bg-indigo-600'" :style="{ width: `${usage.percentage}%` }"></div></div><p class="mt-3 text-sm text-slate-500">{{ usage.at_limit ? "You've reached your document limit. Upgrade to create another document." : usage.remaining === null ? 'Unlimited documents available.' : `${usage.remaining} documents remaining` }}</p></div>
      </section>
      <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"><h2 class="text-lg font-bold text-slate-900 dark:text-white">Payment history</h2><div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800"><div v-for="payment in payments.data" :key="payment.id" class="flex items-center justify-between py-3 text-sm"><span>{{ payment.plan?.name ?? 'Subscription' }}</span><span class="capitalize text-slate-500">{{ payment.status }} · {{ payment.currency }} {{ (payment.amount_cents / 100).toFixed(2) }}</span></div><p v-if="!payments.data.length" class="py-4 text-sm text-slate-500">No payments yet.</p></div></section>
    </main>
  </AppLayout>
</template>
