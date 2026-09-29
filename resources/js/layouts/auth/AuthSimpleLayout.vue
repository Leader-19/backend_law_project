<script setup lang="ts">
import BrandLogo from '@/components/BrandLogo.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { BookOpen, CheckCircle2, ShieldCheck, Sparkles } from '@lucide/vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="auth-page flex h-screen items-center justify-center overflow-hidden bg-zinc-50 dark:bg-zinc-950 lg:grid lg:grid-cols-2">
        <!-- Left Panel -->
        <aside class="relative hidden h-full min-h-screen flex-col justify-end overflow-hidden p-10 lg:flex">
            <img
                src="/images/book-removebg-preview.png"
                alt=""
                class="pointer-events-none absolute -left-8 -top-8 w-64 max-w-none select-none"
            />

            <div class="relative z-10">
                <div class="flex items-center gap-3">
                    <Link :href="home()">
                        <BrandLogo />
                    </Link>
                    <span class="rounded-full border border-zinc-200 bg-white px-2.5 py-1 text-xs font-medium text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                        Welcome back
                    </span>
                </div>
                <p class="mt-4 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
                    ចូលរួមរៀនសូត្រ និងស្វែងរកឯកសារច្បាប់យ៉ាងងាយស្រួល តាមរយៈបណ្ណាល័យឯកសារ ការធ្វើតេស្ត និងការតាមដានសមត្ថភាពផ្ទាល់ខ្លួន។
                </p>

                <div class="mt-10 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-zinc-400 dark:text-zinc-500">
                    <span>© {{ new Date().getFullYear() }} SPRITUP. All rights reserved.</span>
                </div>
            </div>
        </aside>

        <!-- Right Form Section -->
        <main class="flex h-full w-full items-center justify-center overflow-y-auto p-6 sm:p-10">
            <div class="w-full max-w-sm">
                <Link :href="home()" class="mb-8 lg:hidden">
                    <BrandLogo />
                </Link>

                <div class="mb-6">
                    <h1 class="font-display text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ title }}</h1>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ description }}</p>
                </div>

                <slot />
            </div>
        </main>
    </div>
</template>
