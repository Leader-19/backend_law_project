<script setup lang="ts">
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { BookOpen, CheckCircle2, ShieldCheck, Sparkles } from '@lucide/vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="min-h-svh bg-slate-50 dark:bg-slate-950 lg:grid lg:grid-cols-2">
        <aside class="relative hidden overflow-hidden bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-900 p-12 text-white lg:flex lg:flex-col justify-between">
            <!-- Ambient glow patterns -->
            <div class="absolute inset-0 opacity-25 [background-image:radial-gradient(circle_at_20%_20%,white_0,transparent_25%),radial-gradient(circle_at_80%_75%,white_0,transparent_30%)]" />
            <div class="absolute -bottom-24 -left-24 size-96 rounded-full bg-blue-500/20 blur-3xl pointer-events-none" />
            <div class="absolute -top-24 -right-24 size-96 rounded-full bg-purple-500/20 blur-3xl pointer-events-none" />

            <Link :href="home()" class="relative z-10 flex items-center gap-3 text-xl font-bold tracking-tight">
                <img src="/logo.jpg" alt="SPRITUP" class="size-11 rounded-xl object-contain bg-white p-1 shadow-lg shadow-indigo-950/30" />
                <span>SPRITUP</span>
            </Link>

            <div class="relative z-10 my-auto max-w-lg space-y-8">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-semibold uppercase tracking-wider backdrop-blur-md">
                    <Sparkles class="size-3.5 text-amber-300" />
                    <span>Legal Learning & Knowledge Platform</span>
                </div>

                <div class="space-y-4">
                    <h2 class="text-4xl font-extrabold leading-tight tracking-tight text-white">
                        ចូលរួមរៀនសូត្រ និងស្វែងរកឯកសារច្បាប់យ៉ាងងាយស្រួល
                    </h2>
                    <p class="text-base leading-relaxed text-blue-100/90">
                        SPRITUP provides unified access to legal documents, interactive practice tests, reading resources, and real-time learning analytics.
                    </p>
                </div>

                <!-- Feature highlights -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center gap-3 text-sm text-blue-100">
                        <div class="flex size-7 items-center justify-center rounded-lg bg-white/10 backdrop-blur-sm">
                            <BookOpen class="size-4 text-emerald-300" />
                        </div>
                        <span>បណ្ណាល័យឯកសារច្បាប់ និងអត្ថបទសិក្សាសម្បូរបែប</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-blue-100">
                        <div class="flex size-7 items-center justify-center rounded-lg bg-white/10 backdrop-blur-sm">
                            <ShieldCheck class="size-4 text-emerald-300" />
                        </div>
                        <span>ប្រព័ន្ធសុវត្ថិភាព និងការគ្រប់គ្រងគណនីទំនើប</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-blue-100">
                        <div class="flex size-7 items-center justify-center rounded-lg bg-white/10 backdrop-blur-sm">
                            <CheckCircle2 class="size-4 text-emerald-300" />
                        </div>
                        <span>ការធ្វើតេស្តសាកល្បង និងតាមដានសមត្ថភាពផ្ទាល់ខ្លួន</span>
                    </div>
                </div>
            </div>

            <p class="relative z-10 text-xs text-blue-200/80">
                © {{ new Date().getFullYear() }} SPRITUP. All rights reserved.
            </p>
        </aside>

        <main class="flex min-h-svh items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-md">
                <Link :href="home()" class="mb-8 flex items-center gap-3 font-bold text-slate-900 dark:text-white lg:hidden">
                    <img src="/logo.jpg" alt="SPRITUP" class="size-10 rounded-xl object-contain shadow-md" />
                    <span class="text-xl">SPRITUP</span>
                </Link>
                <div class="mb-6 space-y-1.5">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ title }}</h1>
                    <p class="text-sm leading-6 text-slate-500 dark:text-slate-400">{{ description }}</p>
                </div>
                <slot />
            </div>
        </main>
    </div>
</template>
