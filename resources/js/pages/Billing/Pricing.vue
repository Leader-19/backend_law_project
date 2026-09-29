<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Check, Sparkles } from 'lucide-vue-next';

type Plan = { id: number; name: string; slug: string; description: string; currency: string; monthly_price_cents: number | null; yearly_price_cents: number | null; max_documents: number | null; features: string[] | null };
const props = defineProps<{ plans: Plan[]; current_plan: string }>();
const interval = ref<'monthly' | 'yearly'>('monthly');
const format = (cents: number | null, currency: string) => new Intl.NumberFormat(undefined, { style: 'currency', currency }).format((cents ?? 0) / 100);
const annualSavings = (plan: Plan) => plan.monthly_price_cents && plan.yearly_price_cents ? Math.max(0, plan.monthly_price_cents * 12 - plan.yearly_price_cents) : 0;
const price = (plan: Plan) => interval.value === 'yearly' ? plan.yearly_price_cents : plan.monthly_price_cents;
const choose = (plan: Plan) => {
    if (plan.slug === 'free') return;
    router.get('/payment/receipt', { plan_id: plan.id });
};
const label = computed(() => interval.value === 'monthly' ? '/ month' : '/ year');
</script>

<template>
  <Head title="Plans & pricing" />
  <AppLayout :breadcrumbs="[{ title: 'Plans & pricing', href: '/pricing' }]">
    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-8">
      <div class="mx-auto max-w-2xl text-center">
        <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300"><Sparkles class="h-4 w-4" /> Simple, transparent pricing</div>
        <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">Choose room to grow</h1>
        <p class="mt-3 text-slate-600 dark:text-slate-300">Your documents stay safe if you change plans. You only need to upgrade when you need more capacity.</p>
        <div class="mx-auto mt-7 inline-flex rounded-[5px] bg-slate-100 p-1 dark:bg-slate-800">
          <button @click="interval = 'monthly'" :class="interval === 'monthly' ? 'bg-white text-slate-900 shadow dark:bg-slate-700 dark:text-white' : 'text-slate-500'" class="rounded-lg px-4 py-2 text-sm font-semibold">Monthly</button>
          <button @click="interval = 'yearly'" :class="interval === 'yearly' ? 'bg-white text-slate-900 shadow dark:bg-slate-700 dark:text-white' : 'text-slate-500'" class="rounded-lg px-4 py-2 text-sm font-semibold">Yearly <span class="ml-1 text-emerald-600">Save</span></button>
        </div>
      </div>
      <section class="mt-10 grid gap-6 md:grid-cols-3">
        <article v-for="plan in plans" :key="plan.id" class="relative flex flex-col rounded-[5px] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900" :class="plan.slug === 'pro' ? 'ring-2 ring-indigo-500' : ''">
          <span v-if="plan.slug === 'pro'" class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-indigo-600 px-3 py-1 text-xs font-bold text-white">Most popular</span>
          <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ plan.name }}</h2>
          <p class="mt-2 min-h-12 text-sm text-slate-500">{{ plan.description }}</p>
          <div class="mt-5"><span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ format(price(plan), plan.currency) }}</span><span v-if="plan.slug !== 'free'" class="text-slate-500"> {{ label }}</span></div>
          <p v-if="interval === 'yearly' && annualSavings(plan)" class="mt-2 text-sm font-medium text-emerald-600">Save {{ format(annualSavings(plan), plan.currency) }} each year</p>
          <p class="mt-4 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ plan.max_documents === null ? 'Unlimited documents' : `${plan.max_documents} documents` }}</p>
          <ul class="mt-5 flex-1 space-y-3 text-sm text-slate-600 dark:text-slate-300"><li v-for="feature in plan.features ?? []" :key="feature" class="flex gap-2"><Check class="h-4 w-4 shrink-0 text-emerald-500" />{{ feature }}</li></ul>
          <button @click="choose(plan)" :disabled="plan.slug === current_plan || plan.slug === 'free'" class="mt-7 rounded-[5px] px-4 py-2.5 text-sm font-bold transition" :class="plan.slug === current_plan || plan.slug === 'free' ? 'cursor-default bg-slate-100 text-slate-500 dark:bg-slate-800' : 'bg-indigo-600 text-white hover:bg-indigo-700'">{{ plan.slug === current_plan ? 'Current plan' : plan.slug === 'free' ? 'Included free' : 'Choose plan' }}</button>
        </article>
      </section>
      <p class="mt-8 text-center text-sm text-slate-500">Pay with the displayed QR code and upload your receipt. Your plan changes only after approval.</p>
    </main>
  </AppLayout>
</template>
