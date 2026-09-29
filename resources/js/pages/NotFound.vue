<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
// import { AlertCircle, Home } from '@lucide/vue';
import { AlertCircle, Home } from '@lucide/vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Page Not Found" />
        <div class="flex h-full flex-1 flex-col items-center justify-center p-8">
            <div class="text-center max-w-md">
                <div class="mx-auto w-20 h-20 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-6">
                    <AlertCircle class="h-10 w-10 text-slate-400" />
                </div>
                <h1 class="text-4xl font-extrabold text-slate-900 dark:text-white mb-2">404</h1>
                <p class="text-lg font-semibold text-slate-700 dark:text-slate-300 mb-2">Page Not Found</p>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-8">
                    The page you are looking for does not exist or has been moved.
                </p>
                <Link
                    href="/dashboard"
                    class="inline-flex items-center gap-2 rounded-[5px] bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition-colors shadow-lg shadow-blue-500/25"
                >
                    <Home class="h-4 w-4" />
                    Back to Dashboard
                </Link>
            </div>
        </div>

</template>
